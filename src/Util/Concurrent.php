<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Util;

use Serendipity\Job\Contract\StdoutLoggerInterface;
use Hyperf\Engine\Channel;

/**
 * @method bool isFull()
 * @method bool isEmpty()
 */
class Concurrent
{
    /**
     * @var Channel
     */
    protected Channel $channel;

    /**
     * @var int
     */
    protected int $limit;

    public function __construct (int $limit)
    {
        $this->limit = $limit;
        $this->channel = new Channel($limit);
    }

    public function __call ($name, $arguments)
    {
        if (in_array($name, [ 'isFull', 'isEmpty' ])) {
            return $this->channel->{$name}(...$arguments);
        }
    }

    public function getLimit (): int
    {
        return $this->limit;
    }

    public function length (): int
    {
        return $this->channel->getLength();
    }

    public function getLength (): int
    {
        return $this->channel->getLength();
    }

    public function getRunningCoroutineCount (): int
    {
        return $this->getLength();
    }

    public function create (callable $callable): void
    {
        $this->channel->push(true);

        Coroutine::create(function () use ($callable) {
            try {
                $callable();
            } catch (\Throwable $exception) {
                if (ApplicationContext::hasContainer()) {
                    $container = ApplicationContext::getApplication()
                                                   ->getContainer();
                    if ($container->has(StdoutLoggerInterface::class)) {
                        $logger = $container->get(StdoutLoggerInterface::class);
                        $logger->error($exception->getMessage());
                    }
                }
            } finally {
                $this->channel->pop();
            }
        });
    }
}
