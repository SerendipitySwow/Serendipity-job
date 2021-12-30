<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation;

use SwowCloud\Job\Workflow\Implementation\Entities\State;
use SwowCloud\Job\Workflow\Implementation\Entities\TransitionWithData;
use SwowCloud\Job\Workflow\Interfaces\StateAwareInterface;

class StateMachine extends AbstractEngine
{
    /**
     * A shortcut to avoid getting a transition.
     */
    public function processInput(StateAwareInterface $item, array $input): void
    {
        $transition = new TransitionWithData($item->getState(), $input, new State(''));
        $this->execute($item, $transition);
    }
}
