<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Repositories;

use ArrayIterator;
use SwowCloud\Job\Workflow\Implementation\Traits;

class ArrayRepository extends AbstractTraversable
{
    use Traits\StateTraversion;
    use Traits\Plantable;

    private array $transitions;

    public function __construct(array $transitions)
    {
        $this->transitions = $transitions;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->transitions);
    }
}
