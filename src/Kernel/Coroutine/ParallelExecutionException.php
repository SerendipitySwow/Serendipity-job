<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Coroutine;

use RuntimeException;

class ParallelExecutionException extends RuntimeException
{
    private array $results;

    private array $throwables;

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): void
    {
        $this->results = $results;
    }

    public function getThrowables(): array
    {
        return $this->throwables;
    }

    public function setThrowables(array $throwables): array
    {
        return $this->throwables = $throwables;
    }
}
