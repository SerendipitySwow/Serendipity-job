<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Logger;

use Serendipity\Job\Kernel\Logger\StdoutLogger;
use Serendipity\Job\Kernel\Provider\AbstractProvider;

class LoggerProvider extends AbstractProvider
{
    public function bootApp(): void
    {
        $this->container()
            ->get(StdoutLogger::class);
    }
}
