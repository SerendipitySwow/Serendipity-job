<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Interfaces;

interface StateInterface extends EquatableInterface, IdentifiableInterface
{
    /**
     * Returns a string that can uniquely identify the state. Eg: "new" or "active"
     */
    public function getName(): string;

    public function __toString(): string;
}
