<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation;

use Serendipity\Job\Workflow\Implementation\Entities\Transition;
use Serendipity\Job\Workflow\Interfaces\StateAwareInterface;
use Serendipity\Job\Workflow\Interfaces\StateInterface;

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
