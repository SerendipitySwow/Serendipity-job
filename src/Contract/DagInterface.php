<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Contract;

use SwowCloud\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

interface DagInterface
{
    /**
     * Get dag Token
     */
    public function getIdentity(): int|string;

    /**
     * Get dag action time
     */
    public function getTimeout(): int;

    /**
     * @param array<int|string,string[]> $results
     */
    public function run(array $results): mixed;

    public function runConcurrentMySQLPattern(ConcurrentMySQLPattern $pattern): mixed;

    public function isNext(): bool;
}
