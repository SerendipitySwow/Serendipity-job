<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Nsq\Consumer;

use Carbon\Carbon;
use InvalidArgumentException;
use Serendipity\Job\Constant\Statistical;
use Serendipity\Job\Constant\Task;
use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Contract\JobInterface;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Event\UpdateJobEvent;
use Serendipity\Job\Kernel\Lock\RedisLock;
use Serendipity\Job\Redis\Lua\Hash\Incr;
use Serendipity\Job\Util\Coroutine;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Nsq;
use SerendipitySwow\Nsq\Result;
use Throwable;

/**
 * TODO 开启10个消费者时，重复消费失败的任务还是有问题.
 */
class TaskConsumer extends AbstractConsumer
{
    protected const TASK_CONSUMER_REDIS_PREFIX = 'TaskIDEntity#%s-%s';

    public function consume(Message $message): ?string
    {
        $redis = $this->redis();
        $job = $this->deserializeMessage($message);
        if (!$job && !$job instanceof JobInterface) {
            $this->logger->error('Invalid task#' . $message->getBody());

            return Result::DROP;
        }
        //判断消息是否被重复消费.
        if ($redis->get(sprintf(static::TASK_CONSUMER_REDIS_PREFIX, $job->getIdentity(), $job->getCounter())) >= 1) {
            $this->logger->error(sprintf('Message %s has been consumed#', $job->getIdentity()));

            return Result::DROP;
        }
        //如果任务执行完成则删除此条消息
        if (DB::fetch('select * from task where id = ? and status = ?', [$job->getIdentity(), Task::TASK_SUCCESS])) {
            $this->logger->debug('Task is successfully');

            return Result::DROP;
        }
        /**
         * @var RedisLock $lock
         */
        $lock = make(RedisLock::class, [$redis]);
        /**
         * @var Incr $incr
         */
        $incr = make(Incr::class);
        if ($lock->lock(sprintf('%s#%s', $job->getIdentity(), $job->getCounter()), ($job->getTimeout() / 1000) + random_int(1, 5))) {
            return $this->waiter->wait(function () use ($job, $incr, $lock) {
                try {
                    //协程退出前释放锁
                    Coroutine::defer(fn () => $lock->unlock(sprintf('%s#%s', $job->getIdentity(), $job->getCounter())));
                    //修改当前那个协程在执行此任务
                    DB::execute(
                        sprintf(
                            'update task set coroutine_id = %s,status = %s where id = %s;',
                            \Swow\Coroutine::getCurrent()
                                ->getId(),
                            Task::TASK_ING,
                            $job->getIdentity(),
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
                    $incr->eval([Statistical::TASK_FAILURE,  $this->config->get('consumer.task_redis_cache_time')]);
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
            }, $job->getTimeout());
        }

        return Result::DROP;
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
                Coroutine::create(function () use ($nsq, $message, $job) {
                    $json = json_encode(
                        array_merge([
                            'body' => json_decode(
                                $message,
                                true,
                                512,
                                JSON_THROW_ON_ERROR
                            ),
                        ], ['class' => $job::class]),
                        JSON_THROW_ON_ERROR
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
            }
            $this->dingTalk->text(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            throw $e;
        }
    }

    protected function deserializeMessage(Message $message): mixed
    {
        $body = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
        /*
         * @var JobInterface $job
         */
        return $this->serializer->deserialize(
            json_encode($body['body'] ?? '', JSON_THROW_ON_ERROR),
            $body['class'] ?? throw new InvalidArgumentException('Unknown class.')
        );
    }
}
