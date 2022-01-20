<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace App\Dag\Task;

use SwowCloud\Job\Contract\DagInterface;
use SwowCloud\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task4 implements DagInterface
{
    public bool $next;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * {@inheritDoc}
     */
    public function Run(array $results): mixed
    {
        echo "Task3::run()\n";

        return true;
    }

    public function isNext(): bool
    {
        return $this->next;
    }

    public function getIdentity(): int|string
    {
        return 3;
    }

    public function getTimeout(): int
    {
        return 5;
    }

    public function runConcurrentMySQLPattern(ConcurrentMySQLPattern $pattern): mixed
    {
        // TODO: Implement runConcurrentMySQLPattern() method.
    }
}
