<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Hyperf\Contract\ConfigInterface;
use Serendipity\Job\Config\ConfigFactory;
use Serendipity\Job\Kernel\Logger\StdoutLogger;
use SwowCloud\Contract\StdoutLoggerInterface;

return [
    ConfigInterface::class => ConfigFactory::class,
    StdoutLoggerInterface::class => StdoutLogger::class,
    Redis::class => SwowCloud\Redis\Redis::class,
];
