<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types = 1);

namespace App\Dag\Task
{

    use SwowCloud\Job\Contract\DagInterface;
    use SwowCloud\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

    class Task1 implements DagInterface
    {
        public function __construct(string $startDate, string $endDate)
        {
            $this->startDate = $startDate;
            $this->endDate   = $endDate;
        }

        /**
         * {@inheritDoc}
         */
        public function run(array $results) : int|bool
        {
            echo "Task1::run()\n";
            sleep(20);

            return true;
        }

        public function isNext() : bool
        {
            return true;
        }

        public function getIdentity() : int|string
        {
            return 1;
        }

        public function getTimeout() : int
        {
            return 5;
        }

        public function runConcurrentMySQLPattern(ConcurrentMySQLPattern $pattern) : mixed
        {
        }
    }
}
