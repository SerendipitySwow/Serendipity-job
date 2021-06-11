<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

use Serendipity\Job\Config\ConfigFactory;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Logger\StdoutLogger;

return [
    ConfigInterface::class => ConfigFactory::class,
    StdoutLoggerInterface::class => StdoutLogger::class,
];
