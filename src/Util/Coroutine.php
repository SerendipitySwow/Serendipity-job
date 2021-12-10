<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util;

use Hyperf\Engine\Coroutine as Co;
use Hyperf\Engine\Exception\CoroutineDestroyedException;
use Hyperf\Engine\Exception\RunningInNonCoroutineException;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;
use SwowCloud\Contract\StdoutLoggerInterface;
use Throwable;

class Coroutine
{
    /**
     * Returns the current coroutine ID.
     * Returns -1 when running in non-coroutine context.
     */
    public static function id(): int
    {
        return Co::id();
    }

    public static function defer(callable $callable): void
    {
        Co::defer($callable);
    }

    public static function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1000 * 1000));
    }

    /**
     * Returns the parent coroutine ID.
     * Returns 0 when running in the top level coroutine.
     *
     * @throws RunningInNonCoroutineException when running in non-coroutine context
     * @throws CoroutineDestroyedException when the coroutine has been destroyed
     */
    public static function parentId(?int $coroutineId = null): int
    {
        return Co::pid($coroutineId);
    }

    /**
     * @return int Returns the coroutine ID of the coroutine just created.
     *             Returns -1 when coroutine create failed.
     */
    public static function create(callable $callable): int
    {
        $coroutine = Co::create(function () use ($callable) {
            try {
                call($callable);
            } catch (Throwable $throwable) {
                if (ApplicationContext::hasContainer()) {
                    $container = ApplicationContext::getContainer();
                    if ($container->has(StdoutLoggerInterface::class)) {
                        /* @var LoggerInterface $logger */
                        $logger = $container->get(StdoutLoggerInterface::class);
                        $logger->warning(sprintf(
                            'Uncaptured exception[%s] {%s} detected in %s::%d.',
                            get_class($throwable),
                            $throwable->getMessage(),
                            $throwable->getFile(),
                            $throwable->getLine()
                        ));
                    }
                }
            }
        });

        try {
            return $coroutine->getId();
        } catch (Throwable) {
            return -1;
        }
    }

    public static function inCoroutine(): bool
    {
        return Co::id() > 0;
    }
}
