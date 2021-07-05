<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Crontab;

use Carbon\Carbon;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Util\Coroutine;

class CoroutineStrategy
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatch(Crontab $crontab): void
    {
        Coroutine::create(function () use ($crontab) {
            if ($crontab->getExecuteTime() instanceof Carbon) {
                $wait = $crontab->getExecuteTime()
                    ->getTimeStamp() - time();
                $wait > 0 && sleep($wait);
                $executor = $this->container->get(Executor::class);
                $executor->execute($crontab);
            }
        });
    }
}
