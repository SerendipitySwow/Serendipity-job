<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Interfaces;

interface EquatableInterface
{
    /**
     * Returns true if this object equals the $other object.
     *
     * @param $other
     */
    public function equals($other): bool;
}
