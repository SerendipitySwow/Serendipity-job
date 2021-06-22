<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Event;

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
