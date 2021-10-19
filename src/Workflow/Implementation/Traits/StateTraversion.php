<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation\Traits;

use Serendipity\Job\Workflow\Interfaces\StateInterface;
use Serendipity\Job\Workflow\Interfaces\TransitionInterface;
use Serendipity\Job\Workflow\Interfaces\TransitionRepositoryInterface;

/**
 * Assuming the actual class is a {@see TransitionRepositoryInterface}, this trait provide methods for navigating
 * between states via transitions, either forward (old state to new state) or backward (new state to old state).
 */
trait StateTraversion
{
    public function getForwardTransitions(StateInterface $state): array
    {
        /* @var $this TransitionRepositoryInterface */
        return array_values(array_filter(
            iterator_to_array($this->all()),
            static function (TransitionInterface $transition) use ($state): bool {
                return $transition->getOldState()->equals($state);
            }
        ));
    }

    public function getBackwardTransitions(StateInterface $state): array
    {
        /* @var $this TransitionRepositoryInterface */
        return array_values(array_filter(
            iterator_to_array($this->all()),
            static function (TransitionInterface $transition) use ($state): bool {
                return $transition->getNewState()->equals($state);
            }
        ));
    }
}
