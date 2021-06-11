<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Dag\Interfaces;

use Serendipity\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

interface DagInterface
{
    public function Run(ConcurrentMySQLPattern $pattern): mixed;

    public function isNext(): bool;
}
