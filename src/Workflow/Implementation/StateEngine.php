<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation;

use SwowCloud\Job\Workflow\Implementation\Entities\Transition;
use SwowCloud\Job\Workflow\Interfaces\StateAwareInterface;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;

class StateEngine extends AbstractEngine
{
    /**
     * A shortcut to avoid getting a transition.
     */
    public function changeState(StateAwareInterface $item, StateInterface $newState): void
    {
        $transition = new Transition($item->getState(), $newState);
        $this->execute($item, $transition);
    }
}
