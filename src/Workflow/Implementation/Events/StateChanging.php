<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation\Events;

use Serendipity\Job\Workflow\Interfaces\StateAwareInterface;
use Serendipity\Job\Workflow\Interfaces\StateInterface;

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
