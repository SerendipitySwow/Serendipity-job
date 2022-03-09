<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Constant;

/**
 * Class Statistical
 */
class Statistical
{
    public const TASK_SUCCESS = 'task-statistical-success'; // 成功

    public const TASK_FAILURE = 'task-statistical-failure'; // 失败

    public const TASK_DELAY = 'task-statistical-delay'; // 延迟

    public const TASK_RESERVED = 'task-statistical-reserved';

    public const TASK_TERMINATION = 'task-statistical-termination'; // 终止

    public const DAG_SUCCESS = 'dag-statistical-success'; // 成功

    public const DAG_FAILURE = 'dag-statistical-failure'; // 失败

    public const DAG_DELAY = 'dag-statistical-delay'; // 延迟

    public const DAG_RESERVED = 'dag-statistical-reserved';

    public const DAG_TERMINATION = 'dag-statistical-termination'; // 终止
}
