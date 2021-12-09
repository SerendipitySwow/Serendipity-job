<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Nsq\Consumer;

use Hyperf\Utils\Pipeline;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\SerializerInterface;
use Serendipity\Job\Dingtalk\DingTalk;
use Serendipity\Job\Logger\LoggerFactory;
use Serendipity\Job\Serializer\SymfonySerializer;
use Serendipity\Job\Util\Waiter;
use SerendipitySwow\Nsq\Message;
use SwowCloud\Redis\Redis;
use SwowCloud\Redis\RedisFactory;
use SwowCloud\Redis\RedisProxy;

abstract class AbstractConsumer
{
    public const TOPIC_PREFIX = 'serendipity-job-';

    protected string $topic = '';

    protected string $channel = '';

    protected string $name = 'NsqConsumer';

    protected string $redisPool = 'default';

    protected ?LoggerInterface $logger = null;

    protected ContainerInterface $container;

    protected ?SerializerInterface $serializer = null;

    protected ?Waiter $waiter = null;

    protected ?Pipeline $pipeline = null;

    protected ?ConfigInterface $config = null;

    protected ?DingTalk $dingTalk = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get(LoggerFactory::class)
            ->get('serendipity', 'job');
        $this->waiter = $this->container->get(Waiter::class);
        $this->serializer = $this->container->get(SymfonySerializer::class);
        $this->pipeline = $this->container->get(Pipeline::class);
        $this->config = $this->container->get(ConfigInterface::class);
        $this->dingTalk = $this->container->get(DingTalk::class);
    }

    abstract public function consume(Message $message): ?string;

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNums(): int
    {
        return $this->nums;
    }

    public function setNums(int $nums): self
    {
        $this->nums = $nums;

        return $this;
    }

    public function setRedisPool(string $redisPool): self
    {
        $this->redisPool = $redisPool;

        return $this;
    }

    public function getRedisPool(): string
    {
        return $this->redisPool;
    }

    protected function redis(string $redisPool = null): RedisProxy|Redis
    {
        return $this->container->get(RedisFactory::class)
            ->get($redisPool ?? $this->getRedisPool());
    }
}
