<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Task;

use Carbon\Carbon;

class EchoCrontab
{
    public function execute(): void
    {
        echo Carbon::now()->toDateTimeString() . PHP_EOL;
    }
}
