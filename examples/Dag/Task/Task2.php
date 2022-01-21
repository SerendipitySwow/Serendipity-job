<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace App\Dag\Task;

use App\Dag\Exception\DagException;
use SwowCloud\Job\Contract\DagInterface;
use SwowCloud\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task2 implements DagInterface
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
    public function run(array $results): int|bool
    {
        if ($results['taskNo2'] === true) {
            echo "Task2::run()\n";

            return true;
        }
        throw new DagException('never done!');
    }

    public function isNext(): bool
    {
        return $this->next;
    }

    public function getIdentity(): int|string
    {
        return 2;
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