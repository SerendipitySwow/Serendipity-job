<?php

declare(strict_types = 1);

namespace Serendipity\Job\Contract;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Swow\Socket;

interface ServerInterface
{

    public function __construct(ContainerInterface $container, LoggerInterface $logger, EventDispatcherInterface $dispatcher);

    /**
     * @return \Serendipity\Job\Contract\ServerInterface
     */
    public function getServer() : self;

    public function start() : Socket;
}
