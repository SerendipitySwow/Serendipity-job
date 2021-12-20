<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Crontab;

use Carbon\Carbon;
use Hyperf\Utils\Coroutine as HyperfCo;
use Psr\Container\ContainerInterface;

class CoroutineStrategy
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatch(Crontab $crontab): void
    {
        HyperfCo::create(function () use ($crontab) {
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
