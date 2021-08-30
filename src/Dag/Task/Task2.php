<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dag\Task;

use Serendipity\Job\Contract\DagInterface;
use Serendipity\Job\Dag\Exception\DagException;
use Serendipity\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

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
