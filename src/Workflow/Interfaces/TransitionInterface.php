<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Interfaces;

interface TransitionInterface extends EquatableInterface, IdentifiableInterface
{
    /**
     * Return the old state.
     */
    public function getOldState(): StateInterface;

    /**
     * Return the new state.
     */
    public function getNewState(): StateInterface;

    public function __toString(): string;
}
