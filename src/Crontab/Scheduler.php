<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Crontab;

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
        foreach ($this->getSchedules() as $schedule) {
            $this->schedules->enqueue($schedule);
        }

        return $this->schedules;
    }

    /**
     * @return array<int,\SwowCloud\Job\Crontab\Crontab>
     */
    protected function getSchedules(): array
    {
        return $this->crontabManager->parse();
    }
}
