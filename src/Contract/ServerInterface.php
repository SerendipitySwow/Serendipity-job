<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Contract;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swow\Socket;

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
