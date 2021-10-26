<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Interfaces;

interface IdentifiableInterface
{
    /**
     * Returns a string that uniquely identifies the content of this object in relation to objects of the same type.
     */
    public function getId(): string;
}
