<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Event;

class UpdateWorkflowEvent
{
    public int $id;

    public int $status;

    public const UPDATE_WORKFLOW = 'update-workflow';

    public function __construct(int $id, int $status)
    {
        $this->id = $id;
        $this->status = $status;
    }
}
