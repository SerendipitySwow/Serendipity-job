<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Crontab;

use JetBrains\PhpStorm\Pure;
use SplQueue;

class Scheduler
{
    protected CrontabManager $crontabManager;

    protected SplQueue $schedules;

    #[Pure]
    public function __construct(CrontabManager $crontabManager)
    {
        $this->schedules = new SplQueue();
        $this->crontabManager = $crontabManager;
    }

    public function schedule(): SplQueue
    {
        foreach ($this->getSchedules() ?? [] as $schedule) {
            $this->schedules->enqueue($schedule);
        }

        return $this->schedules;
    }

    protected function getSchedules(): array
    {
        return $this->crontabManager->parse();
    }
}
