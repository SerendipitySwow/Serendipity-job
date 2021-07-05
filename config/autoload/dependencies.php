<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Serendipity\Job\Config\ConfigFactory;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Logger\StdoutLogger;
use Serendipity\Job\Redis\Redis;

return [
    ConfigInterface::class => ConfigFactory::class,
    StdoutLoggerInterface::class => StdoutLogger::class,
    \Redis::class => Redis::class,
];
