<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation\Events;

use Serendipity\Job\Workflow\Interfaces\StateAwareInterface;
use Serendipity\Job\Workflow\Interfaces\StateInterface;

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
