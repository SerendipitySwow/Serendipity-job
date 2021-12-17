<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

/*
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Nsq\Consumer;

use Hyperf\Utils\Codec\Json;
use InvalidArgumentException;
use Serendipity\Job\Contract\JobInterface;
use Serendipity\Job\Util\Coroutine as SerendipitySwowCo;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Result;
use SwowCloud\RedisLock\RedisLock;
use function Serendipity\Job\Kernel\serendipity_json_decode;

class TaskConsumer2 extends AbstractConsumer
{
    protected const TASK_CONSUMER_REDIS_PREFIX = 'TaskIDEntity#%s-%s';

    /**
     * @throws \Throwable
     */
    public function consume(Message $message): ?string
    {
        /* 测试redis-lock 性能*/
        SerendipitySwowCo::create(function () use ($message) {
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
            sleep(10);
            $lock->unLock();

            return $this->chan->push(Result::ACK);
        });

        return $this->chan->pop();
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
