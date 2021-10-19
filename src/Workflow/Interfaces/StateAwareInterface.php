<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Interfaces;

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
