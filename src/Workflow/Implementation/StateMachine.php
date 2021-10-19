<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation;

use Serendipity\Job\Workflow\Implementation\Entities\State;
use Serendipity\Job\Workflow\Interfaces\StateAwareInterface;

class StateMachine extends AbstractEngine
{
    /**
     * A shortcut to avoid getting a transition.
     */
    public function processInput(StateAwareInterface $item, array $input): void
    {
        $transition = new Serendipity\Job\Workflow\Implementation\Entities\TransitionWithData($item->getState(), $input, new State(''));
        $this->execute($item, $transition);
    }
}
