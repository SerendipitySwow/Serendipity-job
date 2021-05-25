<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Swow;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Serendipity\Job\Contract\ServerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Swow\Socket;

class  Server implements ServerInterface
{
    protected ?Socket $server = null;

    protected ?int $port = null;

    protected ?string $host = null;

    protected int $type = Socket::TYPE_TCP;

    protected int $backlog = 8192;

    protected bool $multi = true;

    public function __construct(ContainerInterface $container, StdoutLoggerInterface $logger = null, ?EventDispatcherInterface $dispatcher = null)
    {
        $this->container    = $container;
        $this->stdoutLogger = $logger;
        $this->dispatcher   = $dispatcher;
    }

    /**
     * @param bool $multi
     */
    public function setMulti(bool $multi) : void
    {
        $this->multi = $multi;
    }

    /**
     * @param int $backlog
     */
    public function setBacklog(int $backlog) : void
    {
        $this->backlog = $backlog;
    }

    /**
     * @param null|int $port
     */
    public function setPort(?int $port) : void
    {
        $this->port = $port;
    }

    /**
     * @param int $type
     */
    public function setType(int $type) : void
    {
        $this->type = $type;
    }

    /**
     * @param null|string $host
     */
    public function setHost(?string $host) : void
    {
        $this->host = $host;
    }

    public function getServer() : Server
    {
        if (!$this->type) {
            $this->stdoutLogger->warning('Swow Socket Type UnKnown#');
        }
        if (!$this->port) {
            $this->stdoutLogger->warning('Swow Socket Port UnKnown#');
        }

        $this->server = new Socket($this->type);

        return $this;
    }

    public function start() : Socket
    {
        $bindFlag = Socket::BIND_FLAG_NONE;
        if ($this->multi) {
            $this->server->setTcpAcceptBalance(true);
            $bindFlag |= Socket::BIND_FLAG_REUSEPORT;
        }
        $this->server->bind($this->host, $this->port, $bindFlag)->listen($this->backlog);
        return $this->server;
    }

}
