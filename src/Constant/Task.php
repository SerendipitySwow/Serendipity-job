<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Constant;

class Task
{
    public const TASK_SUCCESS = 2;

    public const TASK_ING = 1;

    public const TASK_TODO = 0;

    public const TASK_CANCEL = 3;

    public const TASK_ERROR = 4;
}
