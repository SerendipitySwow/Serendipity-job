<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Nsq\Consumer;

use Carbon\Carbon;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Coroutine as HyperfCo;
use InvalidArgumentException;
use Serendipity\Job\Constant\Statistical;
use Serendipity\Job\Constant\Task;
use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Contract\JobInterface;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Event\UpdateJobEvent;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Nsq;
use SerendipitySwow\Nsq\Result;
use Swow\Coroutine as SwowCo;
use SwowCloud\Redis\Lua\Hash\Incr;
use SwowCloud\RedisLock\RedisLock;
use Throwable;
use function Serendipity\Job\Kernel\serendipity_json_decode;
use function Serendipity\Job\Kernel\server_ip;

class TaskConsumer extends AbstractConsumer
{
    protected const TASK_CONSUMER_REDIS_PREFIX = 'TaskIDEntity#%s-%s';

    /**
     * @throws \Throwable
     */
    public function consume(Message $message): ?string
    {
        /* 测试redis-lock 性能*/
        HyperfCo::create(function () use ($message) {
            $job = $this->deserializeMessage($message);
            if (!$job && !$job instanceof JobInterface) {
                $this->logger->error('Invalid task#' . $message->getBody());

                return $this->chan->push(Result::DROP);
            }
            $lock = make(RedisLock::class);
            if (!$lock->lock((string) $job->getIdentity(), (int) ($job->getTimeout() / 1000))) {
                $this->logger->error(sprintf('Task:[%s] Processing#', $job->getIdentity()));

                return $this->chan->push(Result::DROP);
            }
            $incr = make(Incr::class);
            $result = $this->waiter->wait(function () use ($job, $incr) {
                try {
                    //修改当前那个协程在执行此任务,用于取消任务
                    DB::execute(
                        sprintf(
                            'update task set coroutine_id = %s,status = %s,server_ip = "%s"  where id = %s;',
                            SwowCo::getCurrent()
                                ->getId(),
                            Task::TASK_ING,
                            server_ip(),
                            $job->getIdentity()
                        )
                    );
                    $this->handle($job);
                    //记录此消息已被消费而且任务已被执行完成
                    $incr->eval([
                        sprintf(
                            static::TASK_CONSUMER_REDIS_PREFIX,
                            $job->getIdentity(),
                            $job->getCounter()
                        ),
                        $this->config->get('consumer.task_redis_consumer_time'),
                    ]);
                    //加入成功执行统计
                    $incr->eval([Statistical::TASK_SUCCESS, $this->config->get('consumer.task_redis_cache_time')]);
                    $this->container->get(EventDispatcherInterface::class)
                        ->dispatch(
                            new UpdateJobEvent($job->getIdentity(), Task::TASK_SUCCESS),
                            UpdateJobEvent::UPDATE_JOB
                        );
                    $result = Result::ACK;
                } catch (Throwable $e) {
                    //加入失败执行统计
                    $incr->eval([Statistical::TASK_FAILURE, $this->config->get('consumer.task_redis_cache_time')]);
                    $this->logger->error(
                        sprintf(
                            'Uncaptured exception[%s:%s] detected in %s::%d.',
                            get_class($e),
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        ),
                        [
                            'driver' => $job::class,
                        ]
                    );
                    $result = Result::DROP;
                }

                return $result;
            });
            $lock->unLock();

            return $this->chan->push($result);
        });

        return $this->chan->pop();
    }

    /**
     * @throws Throwable
     */
    protected function handle(JobInterface $job): void
    {
        try {
            $this->logger->info(
                sprintf(
                    'Task ID:[%s] Time:[%s] Start Execution#.',
                    $job->getIdentity(),
                    Carbon::now()
                        ->toDateTimeString()
                )
            );
            $this->pipeline->send($job)
                ->through($job->middleware())
                ->then(function (JobInterface $job) {
                    $job->handle();
                });
            $this->logger->info(
                sprintf(
                    'Task ID:[%s] Time:[%s] Completed#.',
                    $job->getIdentity(),
                    Carbon::now()
                        ->toDateTimeString()
                )
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Task ID:[%s] Time:[%s] Error#. Please check details at Dingding Talk.',
                    $job->getIdentity(),
                    Carbon::now()
                        ->toDateTimeString()
                )
            );
            $payload = [
                'last_error' => get_class($e),
                'last_error_message' => $e->getMessage(),
                'counter' => $job->getCounter(),
                'task_id' => $job->getIdentity(),
                'last_error_line' => $e->getLine(),
                'last_error_file' => $e->getFile(),
            ];
            //retry
            if ($job->canRetry($job->getCounter(), $e)) {
                $job->IncreaseCounter();
                $message = $this->serializer->serialize($job);
                $config = $this->config->get(sprintf('nsq.%s', 'default'));

                /**
                 * @var Nsq $nsq
                 *          push nsq
                 */
                $nsq = make(Nsq::class, [$this->container, $config]);
                HyperfCo::create(function () use ($nsq, $message, $job) {
                    $json = Json::encode(
                        array_merge([
                            'body' => serendipity_json_decode(
                                $message
                            ),
                        ], ['class' => $job::class])
                    );
                    $nsq->publish($this->getTopic(), $json, $job->getStep());
                    $this->logger->info(sprintf('TaskConsumer Retry Task:%s#.', $job->getIdentity()));
                    Db::execute(
                        sprintf(
                            "update task set runtime = '%s',status = %s,retry_times = retry_times + 1 where id = %s;",
                            Carbon::now()
                                ->addSeconds($job->getStep())
                                ->toDateTimeString(),
                            Task::TASK_ING,
                            $job->getIdentity()
                        )
                    );
                });
            } else {
                //failed
                $job->failed($payload);
                DB::execute(
                    sprintf(
                        "update task set status = %s,memo = '%s'  where id = %s;",
                        Task::TASK_ERROR,
                        Json::encode($payload),
                        $job->getIdentity()
                    )
                );
            }
            $this->dingTalk->text(Json::encode($payload, JSON_UNESCAPED_UNICODE));
            throw $e;
        }
    }

    /**
     * @return JobInterface
     */
    protected function deserializeMessage(Message $message): mixed
    {
        $body = serendipity_json_decode($message->getBody());

        return $this->serializer->deserialize(
            Json::encode($body['body'] ?? ''),
            $body['class'] ?? throw new InvalidArgumentException('Unknown class.')
        );
    }
}
