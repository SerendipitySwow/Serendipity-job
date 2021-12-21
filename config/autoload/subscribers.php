<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use SwowCloud\Job\Subscriber\CrontabRegisterSubscriber;
use SwowCloud\Job\Subscriber\DbQueryExecutedSubscriber;
use SwowCloud\Job\Subscriber\UpdateJobSubscriber;
use SwowCloud\Job\Subscriber\UpdateWorkflowSubscriber;

return [
    UpdateWorkflowSubscriber::class,
    UpdateJobSubscriber::class,
    CrontabRegisterSubscriber::class,
    DbQueryExecutedSubscriber::class,
];
