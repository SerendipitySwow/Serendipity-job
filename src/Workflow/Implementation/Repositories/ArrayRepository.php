<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation\Repositories;

use ArrayIterator;
use Serendipity\Job\Workflow\Implementation\Traits;

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
