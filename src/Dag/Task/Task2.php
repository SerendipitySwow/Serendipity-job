<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dag\Task;

use Serendipity\Job\Contract\DagInterface;
use Serendipity\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task2 implements DagInterface
{
    public bool $next;

    /**
     * {@inheritDoc}
     */
    public function Run(ConcurrentMySQLPattern $pattern): int | bool
    {
        $sqlquery = 'DELETE FROM `edge` WHERE `edge_id` = 23';

        return $pattern->getPDO()
            ->exec($sqlquery);
    }

    public function isNext(): bool
    {
        return $this->next;
    }

    public function getIdentity (): int|string
    {
       return 2;
    }

    public function getTimeout (): int
    {
       return 5;
    }
}
