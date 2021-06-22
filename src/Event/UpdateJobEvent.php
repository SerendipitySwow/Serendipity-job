<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Event;

class UpdateJobEvent
{
    public object $job;

    public const UPDATE_JOB = 'update-job';

    public function __construct(object $job)
    {
        $this->job = $job;
    }
}
