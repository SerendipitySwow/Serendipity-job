<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Interfaces;

interface StateAwareInterface
{
    /**
     * Returns the old (or current) state.
     */
    public function getState(): StateInterface;

    /**
     * Sets the new state.
     */
    public function setState(StateInterface $newState): void;
}
