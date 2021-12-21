<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Event;

class UpdateJobEvent
{
    public int $id;

    public int $status;

    public const UPDATE_JOB = 'update-job';

    public function __construct(int $id, int $status)
    {
        $this->id = $id;
        $this->status = $status;
    }
}
