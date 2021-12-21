<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Hyperf\Contract\ConfigInterface;
use SwowCloud\Contract\StdoutLoggerInterface;
use SwowCloud\Job\Config\ConfigFactory;
use SwowCloud\Job\Kernel\Logger\StdoutLogger;

return [
    ConfigInterface::class => ConfigFactory::class,
    StdoutLoggerInterface::class => StdoutLogger::class,
    Redis::class => SwowCloud\Redis\Redis::class,
];
