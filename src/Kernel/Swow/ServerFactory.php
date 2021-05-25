<?php

declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Swow;

use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Contract\ServerInterface;
use Swow\Socket;

class ServerFactory
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var \Serendipity\Job\Contract\ServerInterface|null
     */
    protected ?ServerInterface $server = null;

    protected ?EventDispatcherInterface $eventDispatcherInterface = null;

    /**
     * @var null|array
     */
    protected ?array $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config    = $container->get(ConfigInterface::class)->get('server');
    }

    public function start() : Socket
    {
        return $this->getServer()->start();
    }

    public function getServer() : ServerInterface
    {
        if (!$this->server instanceof ServerInterface) {

            $this->server = new Server(
                $this->container
            );
            $this->server->setBacklog($this->config['backlog']);
            $this->server->setHost($this->config['host']);
            $this->server->setPort($this->config['port']);
            $this->server->setMulti($this->config['multi']);
            $this->server->setType($this->config['type']);
        }

        return $this->server->getServer();
    }

}
