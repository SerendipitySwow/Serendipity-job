<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util\Coordinator;

class Constants
{
    /**
     * Swoole onWorkerStart event.
     */
    public const COMMAND_START = 'commandStart';

    /**
     * Swoole onWorkerExit event.
     */
    public const COMMAND_EXIT = 'commandExit';
}
