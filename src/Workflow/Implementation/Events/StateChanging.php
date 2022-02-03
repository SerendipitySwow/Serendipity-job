<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Events;

use SwowCloud\Job\Workflow\Interfaces\StateAwareInterface;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;

class StateChanging
{
    private StateAwareInterface $item;

    private StateInterface $newState;

    /**
     * Event triggered before a state is changed.
     */
    public function __construct(StateAwareInterface $item, StateInterface $newState)
    {
        $this->item = $item;
        $this->newState = $newState;
    }

    public function getItem(): StateAwareInterface
    {
        return $this->item;
    }

    public function getNewState(): StateInterface
    {
        return $this->newState;
    }
}
