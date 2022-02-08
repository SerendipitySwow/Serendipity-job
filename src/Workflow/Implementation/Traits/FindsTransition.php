<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Traits;

use SwowCloud\Job\Workflow\Interfaces\TransitionInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionRepositoryInterface;

/**
 * @mixin TransitionRepositoryInterface
 */
trait FindsTransition
{
    public function find(TransitionInterface $search): ?TransitionInterface
    {
        foreach ($this->all() as $match) {
            if ($search->equals($match)) {
                return $match;
            }
        }

        return null;
    }
}
