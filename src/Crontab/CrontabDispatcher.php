<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Crontab;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use SwowCloud\Contract\StdoutLoggerInterface;

class CrontabDispatcher
{
    public string $name = 'crontab-dispatcher';

    /**
     * @var ConfigInterface
     */
    private mixed $config;

    private Scheduler $scheduler;

    private StdoutLoggerInterface $logger;

    private CoroutineStrategy $strategy;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $this->scheduler = $container->get(Scheduler::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->strategy = $container->get(CoroutineStrategy::class);
    }

    public function isEnable(): bool
    {
        return $this->config->get('crontab.enable', false);
    }

    public function handle(): void
    {
        while (true) {
            $this->sleep();
            $crontabs = $this->scheduler->schedule();
            while (!$crontabs->isEmpty()) {
                $crontab = $crontabs->dequeue();
                $this->strategy->dispatch($crontab);
            }
        }
    }

    private function sleep(): void
    {
        $current = date('s');
        /** @var int<0,60> $sleep */
        $sleep = 60 - $current;
        $this->logger->debug('Crontab dispatcher sleep ' . $sleep . 's.');
        $sleep > 0 && sleep($sleep);
    }
}
