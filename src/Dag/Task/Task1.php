<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dag\Task;

use Serendipity\Job\Dag\Interfaces\DagInterface;
use Serendipity\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task1 implements DagInterface
{
    /**
     * {@inheritDoc}
     */
    public function Run(ConcurrentMySQLPattern $pattern): int | bool
    {
        $start = random_int(1, 999);
        $end = random_int(999, 99999);
        $sqlquery = "INSERT INTO `edge` (`start_vertex`,`end_vertex`) VALUES ({$start},{$end})";

        return $pattern->getPDO()
            ->exec($sqlquery);
    }

    public function isNext(): bool
    {
        return true;
    }
}