<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util;

use Closure;
use Exception;
use Hyperf\Utils\Exception\ExceptionThrower;
use Swow\Channel;
use Throwable;

class Waiter
{
    protected int $pushTimeout = 10;

    protected int $popTimeout = 10;

    public function __construct(int $timeout = 10)
    {
        $this->popTimeout = $timeout;
    }

    /**
     * @param null|int $timeout seconds
     *
     * @throws Throwable
     */
    public function wait(Closure $closure, ?int $timeout = null): mixed
    {
        if ($timeout === null) {
            $timeout = $this->popTimeout;
        }

        $channel = new Channel(1);
        Coroutine::create(function () use ($channel, $closure) {
            try {
                $result = $closure();
            } catch (Throwable $exception) {
                $result = new ExceptionThrower($exception);
            } finally {
                $channel->push($result ?? null, $this->pushTimeout);
            }
        });

        $result = $channel->pop($timeout);
        if ($result === false && $channel->isAvailable()) {
            throw new Exception(sprintf('Channel wait failed, reason: Timed out for %s s', $timeout));
        }
        if ($result instanceof ExceptionThrower) {
            throw $result->getThrowable();
        }

        return $result;
    }
}
