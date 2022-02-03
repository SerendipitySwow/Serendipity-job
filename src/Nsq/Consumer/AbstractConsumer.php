<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Nsq\Consumer;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Engine\Channel;
use Hyperf\Utils\Pipeline;
use Psr\Container\ContainerInterface;
use SwowCloud\Contract\LoggerInterface;
use SwowCloud\Job\Contract\SerializerInterface;
use SwowCloud\Job\Dingtalk\DingTalk;
use SwowCloud\Job\Logger\LoggerFactory;
use SwowCloud\Job\Serializer\SymfonySerializer;
use SwowCloud\Nsq\Message;
use SwowCloud\Redis\Redis;
use SwowCloud\Redis\RedisFactory;
use SwowCloud\Redis\RedisProxy;

abstract class AbstractConsumer
{
    public const TOPIC_PREFIX = 'swow-cloud-job-';

    protected string $topic = '';

    protected string $channel = '';

    protected string $name = 'NsqConsumer';

    protected string $redisPool = 'default';

    protected string $serviceId = '';

    protected ?LoggerInterface $logger = null;

    protected ContainerInterface $container;

    protected ?SerializerInterface $serializer = null;

    protected ?Pipeline $pipeline = null;

    protected ?ConfigInterface $config = null;

    protected ?DingTalk $dingTalk = null;

    private int $nums;

    protected Channel $chan;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get(LoggerFactory::class)
            ->get('serendipity', 'job');
        $this->serializer = $this->container->get(SymfonySerializer::class);
        $this->pipeline = $this->container->get(Pipeline::class);
        $this->config = $this->container->get(ConfigInterface::class);
        $this->dingTalk = $this->container->get(DingTalk::class);
        $this->chan = new Channel();
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

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): void
    {
        $this->serviceId = $serviceId;
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
