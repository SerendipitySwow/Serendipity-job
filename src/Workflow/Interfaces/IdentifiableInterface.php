<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Interfaces;

interface IdentifiableInterface
{
    /**
     * Returns a string that uniquely identifies the content of this object in relation to objects of the same type.
     */
    public function getId(): string;
}
