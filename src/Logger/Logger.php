<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Logger;

use Monolog\Logger as MonoLogger;
use SwowCloud\Contract\LoggerInterface;

class Logger extends MonoLogger implements LoggerInterface
{
}
