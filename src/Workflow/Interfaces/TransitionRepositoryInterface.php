<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Interfaces;

use Traversable;

interface TransitionRepositoryInterface
{
    /**
     * Returns the stored transition matching the $search transition (or null if none found).
     */
    public function find(TransitionInterface $search): ?TransitionInterface;

    /**
     * Returns an array or Traversable object of all transitions.
     */
    public function all(): Traversable;
}
