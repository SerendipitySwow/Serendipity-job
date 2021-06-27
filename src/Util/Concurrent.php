<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util;

use Hyperf\Engine\Channel;
use Hyperf\Utils\ApplicationContext;
use Serendipity\Job\Contract\StdoutLoggerInterface;

/**
 * @method bool isFull()
 * @method bool isEmpty()
 */
class Concurrent
{
    protected Channel $channel;

    protected int $limit;

    public function __construct(int $limit)
    {
        $this->limit = $limit;
        $this->channel = new Channel($limit);
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, ['isFull', 'isEmpty'])) {
            return $this->channel->{$name}(...$arguments);
        }
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function length(): int
    {
        return $this->channel->getLength();
    }

    public function getLength(): int
    {
        return $this->channel->getLength();
    }

    public function getRunningCoroutineCount(): int
    {
        return $this->getLength();
    }

    public function create(callable $callable): void
    {
        $this->channel->push(true);

        Coroutine::create(function () use ($callable) {
            try {
                $callable();
            } catch (\Throwable $exception) {
                if (ApplicationContext::hasContainer()) {
                    $container = ApplicationContext::getContainer();
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
