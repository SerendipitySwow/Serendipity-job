<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

use JetBrains\PhpStorm\Pure;

interface LoggerInterface
{
}

class Logger implements LoggerInterface
{
}

class SubLogger extends Logger
{
}

class Nsq
{
    public Logger $logger;

    #[Pure]
    public function __construct()
    {
        $this->logger = new SubLogger();
    }
}

var_dump(new Nsq());
