<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Interfaces;

interface EquatableInterface
{
    /**
     * Returns true if this object equals the $other object.
     *
     * @param $other
     */
    public function equals($other): bool;
}
