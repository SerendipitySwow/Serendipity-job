<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Contract;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swow\Socket;
use SwowCloud\Contract\StdoutLoggerInterface;

interface ServerInterface
{
    public function __construct(
        ContainerInterface $container,
        StdoutLoggerInterface $logger,
        EventDispatcherInterface $dispatcher
    );

    /**
     * @return ServerInterface
     */
    public function getServer(): self;

    public function start(): Socket;
}
