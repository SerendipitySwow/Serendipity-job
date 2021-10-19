<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation\Traits;

use Serendipity\Job\Workflow\Interfaces\TransitionInterface;
use Serendipity\Job\Workflow\Interfaces\TransitionRepositoryInterface;

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
