<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dag\Task;

use Serendipity\Job\Contract\DagInterface;
use Serendipity\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task1 implements DagInterface
{
    /**
     * {@inheritDoc}
     */
    public function run(): int | bool
    {
        $start = random_int(1, 999);
        $end = random_int(999, 99999);
        $sqlquery = "INSERT INTO `edge` (`start_vertex`,`end_vertex`) VALUES ({$start},{$end})";
    }

    public function isNext(): bool
    {
        return true;
    }

    public function getIdentity(): int | string
    {
        return 1;
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
