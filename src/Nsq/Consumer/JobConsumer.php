<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Nsq\Consumer;

use Carbon\Carbon;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coroutine as HyperfCo;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Swow\Coroutine;
use Swow\Coroutine as SwowCo;
use SwowCloud\Job\Constant\Statistical;
use SwowCloud\Job\Constant\Task;
use SwowCloud\Job\Contract\EventDispatcherInterface;
use SwowCloud\Job\Contract\JobInterface;
use SwowCloud\Job\Db\DB;
use SwowCloud\Job\Event\UpdateJobEvent;
use SwowCloud\Job\Kernel\Logger\AppendRequestIdProcessor;
use SwowCloud\Job\Util\Waiter;
use SwowCloud\Nsq\Message;
use SwowCloud\Nsq\Nsq;
use SwowCloud\Nsq\Result;
use SwowCloud\Redis\Lua\Hash\Incr;
use SwowCloud\RedisLock\RedisLock;
use Throwable;
use function Chevere\Xr\throwableHandler;
use function SwowCloud\Job\Kernel\memory_usage;
use function SwowCloud\Job\Kernel\serendipity_format_throwable;
use function SwowCloud\Job\Kernel\serendipity_json_decode;

class JobConsumer extends AbstractConsumer
{
    protected const TASK_CONSUMER_REDIS_PREFIX = 'TaskIDEntity#%s-%s';

    /**
     * @throws \Throwable
     */
    public function consume(Message $message): ?string
    {
        HyperfCo::create(function () use ($message) {
            $job = $this->deserializeMessage($message);
            if (!$job && !$job instanceof JobInterface) {
                $this->logger->error('Invalid task#' . $message->getBody());

                return $this->chan->push(Result::DROP);
            }
            $waiter = $this->container->get(Waiter::class);
            $lock = make(RedisLock::class);
            if (!$lock->lock((string) $job->getIdentity(), (int) ($job->getTimeout() / 1000))) {
                xr('😭 Task:[%s] Processing');
                $this->logger->error(sprintf('Task:[%s] Processing#', $job->getIdentity()));

                return $this->chan->push(Result::DROP);
            }
            $incr = make(Incr::class);
            try {
                $result = $waiter->wait(function () use ($job, $incr) {
                    try {
                        $currentCo = SwowCo::getCurrent();
                        \Swow\defer(function () use ($currentCo, $job) {
                            //debug trace | push xr trace
                            $trace = $currentCo->getTraceAsList();
                            xr([
                                'trace' => $trace,
                                'trace_id' => Context::getOrSet(AppendRequestIdProcessor::TRACE_ID, Uuid::uuid4()->toString()),
                                'task_id' => $job->getIdentity(),
                                'consul_service_id' => $this->getServiceId(),
                                'message' => sprintf('Task [%s] TraceInfo', $job->getIdentity()),
                            ]);
                            $this->debugLogger->info(Json::encode($trace));
                        });
                        //修改当前那个协程在执行此任务,用于取消任务
                        DB::execute(
                            sprintf(
                                'update task set coroutine_id = %s,status = %s,consul_service_id = "%s"  where id = %s;',
                                $currentCo->getId(),
                                Task::TASK_ING,
                                $this->getServiceId(),
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
                }, (int) ($job->getTimeout() / 1000));
            } catch (Throwable $throwable) {
                $this->logger->error(serendipity_format_throwable($throwable));
                //push xr exception
                throwableHandler($throwable, sprintf(
                    'Coroutine Error#,{task_id:%s,trace_id:{%s},memory_usage:%s}',
                    $job->getIdentity(),
                    Context::getOrSet(AppendRequestIdProcessor::TRACE_ID, Uuid::uuid4()->toString()),
                    memory_usage()
                ));
                //kill task coroutine
                Coroutine::get($waiter->getCoroutineId())?->kill();
                $result = Result::DROP;
            }

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
                'trace_id' => Context::getOrSet(AppendRequestIdProcessor::TRACE_ID, Uuid::uuid4()->toString()),
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
            //push xr exception
            throwableHandler($e, sprintf(
                'Task Error#,{task_id:%s,trace_id:{%s},memory_usage:%s}',
                $job->getIdentity(),
                Context::getOrSet(AppendRequestIdProcessor::TRACE_ID, Uuid::uuid4()->toString()),
                memory_usage()
            ));
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
