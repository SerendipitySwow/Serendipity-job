<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Repositories;

use IteratorAggregate;
use SwowCloud\Job\Workflow\Implementation\Traits\FindsTransition;
use SwowCloud\Job\Workflow\Implementation\Traits\StateTraversion;
use SwowCloud\Job\Workflow\Interfaces\TransitionRepositoryInterface;
use Traversable;

abstract class AbstractTraversable implements TransitionRepositoryInterface, IteratorAggregate
{
    use FindsTransition;
    use StateTraversion;

    public function all(): Traversable
    {
        return $this->getIterator();
    }
}
