<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation\Repositories;

use IteratorAggregate;
use Serendipity\Job\Workflow\Implementation\Traits\FindsTransition;
use Serendipity\Job\Workflow\Implementation\Traits\StateTraversion;
use Serendipity\Job\Workflow\Interfaces\TransitionRepositoryInterface;
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
