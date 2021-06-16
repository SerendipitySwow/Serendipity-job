<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dag\Task;

use Serendipity\Job\Contract\DagInterface;
use Serendipity\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task3 implements DagInterface
{
    /**
     * @var bool
     */
    public $next;

    /**
     * {@inheritDoc}
     */
    public function Run(ConcurrentMySQLPattern $pattern): mixed
    {
        var_dump($pattern->getPDO()
            ->commit());
    }

    public function isNext(): bool
    {
        return $this->next;
    }

    public function getIdentity (): int|string
    {
        return 3;
    }

    public function getTimeout (): int
    {
       return 5;
    }
}
