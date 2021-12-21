<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Interfaces;

interface EngineInterface
{
    /**
     * Transition an item to a new state given a transition.
     */
    public function execute(StateAwareInterface $item, TransitionInterface $transition): void;
}
