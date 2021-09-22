<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Serendipity\Job\Subscriber\CrontabRegisterSubscriber;
use Serendipity\Job\Subscriber\UpdateJobSubscriber;
use Serendipity\Job\Subscriber\UpdateWorkflowSubscriber;

return [
    UpdateWorkflowSubscriber::class,
    UpdateJobSubscriber::class,
    CrontabRegisterSubscriber::class,
];
