<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Task;

use Carbon\Carbon;

class EchoCrontab
{
    public function execute(): void
    {
        echo '____________________RNM___________________________' . PHP_EOL;
        echo Carbon::now()->toDateTimeString() . PHP_EOL;
    }
}
