<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Logger;

use SwowCloud\Job\Kernel\Logger\StdoutLogger;
use SwowCloud\Job\Kernel\Provider\AbstractProvider;

class LoggerProvider extends AbstractProvider
{
    public function bootApp(): void
    {
        $this->container()
            ->get(StdoutLogger::class);
    }
}
