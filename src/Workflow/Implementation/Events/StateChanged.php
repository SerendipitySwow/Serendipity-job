<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Events;

use SwowCloud\Job\Workflow\Interfaces\StateAwareInterface;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;

class StateChanged
{
    private StateAwareInterface $item;

    private StateInterface $oldState;

    /**
     * Event triggered after a state is changed.
     */
    public function __construct(StateAwareInterface $item, StateInterface $oldState)
    {
        $this->item = $item;
        $this->oldState = $oldState;
    }

    public function getItem(): StateAwareInterface
    {
        return $this->item;
    }

    public function getOldState(): StateInterface
    {
        return $this->oldState;
    }
}
