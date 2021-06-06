<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\IOMultiplex;

use Hyperf\Engine\Channel;
use Multiplex\ChannelManager;
use Multiplex\Constract\ClientInterface;
use Multiplex\Constract\HasHeartbeatInterface;
use Multiplex\Constract\IdGeneratorInterface;
use Multiplex\Constract\PackerInterface;
use Multiplex\Constract\SerializerInterface;
use Multiplex\Exception\ChannelClosedException;
use Multiplex\Exception\RecvTimeoutException;
use Multiplex\IdGenerator;
use Multiplex\Packer;
use Multiplex\Packet;
use Multiplex\Serializer\StringSerializer;
use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Util\Collection;
use Swow\Socket;

class Client implements ClientInterface, HasHeartbeatInterface
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var int
     */
    protected int $port;

    /**
     * @var Packer
     */
    protected Packer $packer;

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @var IdGeneratorInterface
     */
    protected IdGeneratorInterface $generator;

    /**
     * @var ?\Hyperf\Engine\Channel
     */
    protected ?Channel $chan;

    /**
     * @var null|resource
     */
    protected ?resource $client;

    /**
     * @var \Serendipity\Job\Util\Collection
     */
    protected Collection $config;

    /**
     * @var ChannelManager
     */
    protected ChannelManager $channelManager;

    /**
     * @var bool
     */
    protected bool $heartbeat = false;

    /**
     * @var null|LoggerInterface
     */
    protected ?LoggerInterface $logger;

    public function __construct(string $name, int $port, ?IdGeneratorInterface $generator = null, ?SerializerInterface $serializer = null, ?PackerInterface $packer = null)
    {
        $this->name           = $name;
        $this->port           = $port;
        $this->packer         = $packer ?? new Packer();
        $this->generator      = $generator ?? new IdGenerator();
        $this->serializer     = $serializer ?? new StringSerializer();
        $this->channelManager = new ChannelManager();
        $this->config         = new Collection([
            'package_max_length' => 1024 * 1024 * 2,
            'recv_timeout'       => 10,
            'connect_timeout'    => 0.5,
            // 'heartbeat' => null,
        ]);
    }

    public function set(array $settings) : Client|static
    {
        $this->config = new Collection($settings);
        return $this;
    }

    public function request($data)
    {
        return $this->recv($this->send($data));
    }

    public function send($data) : int
    {
        $this->loop();

        $this->getChannelManager()->get($id = $this->generator->generate(), true);

        try {
            $payload = $this->packer->pack(
                new Packet(
                    $id,
                    $this->getSerializer()->serialize($data)
                )
            );

            $this->chan->push($payload);
        } catch (\Throwable $exception) {
            is_int($id) && $this->getChannelManager()->close($id);
            throw $exception;
        }

        return $id;
    }

    public function recv(int $id)
    {
        $this->loop();

        $manager = $this->getChannelManager();
        $chan    = $manager->get($id);
        if ($chan === null) {
            throw new ChannelClosedException();
        }

        try {
            $data = $chan->pop($this->config->get('recv_timeout', 10));
            if ($chan->isTimeout()) {
                throw new RecvTimeoutException(sprintf('Recv channel [%d] pop timeout.', $id));
            }

            if ($chan->isClosing()) {
                throw new ChannelClosedException(sprintf('Recv channel [%d] closed.', $id));
            }
        }
        finally {
            $manager->close($id);
        }

        return $data;
    }

    public function getSerializer() : SerializerInterface
    {
        return $this->serializer;
    }

    public function getChannelManager() : ChannelManager
    {
        return $this->channelManager;
    }

    public function close() : void
    {
        $this->client && $this->client = null;
        $this->chan && $this->chan->close();
    }

    public function isHeartbeat() : bool
    {
        $heartbeat = $this->config->get('heartbeat');
        if (!$this->heartbeat && is_numeric($heartbeat)) {
            $this->heartbeat = true;

            Coroutine::create(function () use ($heartbeat)
            {
                while (true) {

                    try {
                        // PING
                        if ($chan = $this->chan and $chan->isEmpty()) {
                            $payload = $this->packer->pack(
                                new Packet(0, Packet::PING)
                            );
                            $chan->push($payload);
                        }
                    } catch (\Throwable $exception) {
                        $this->logger && $this->logger->error((string)$exception);
                    }
                }
            });
        }
    }

    /**
     * @param null|\Serendipity\Job\Contract\LoggerInterface $logger
     *
     * @return static
     */
    public function setLogger(?LoggerInterface $logger) : static
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return resource
     */
    protected function makeClient()
    {

        $fp = stream_socket_client(sprintf('tcp://%s:%s', $this->name, $this->port), $errno, $errstr, 1);
        if ($fp === false) {
            $this->close();
            throw new ClientConnectFailedException($errstr, $errno);
        }
        stream_set_timeout($fp, $this->config->get('connect_timeout', 0.5));
        return $fp;
    }

    protected function loop() : void
    {
        $this->heartbeat();

        if ($this->chan !== null && !$this->chan->isClosing()) {
            return;
        }
        $this->chan   = $this->getChannelManager()->make(65535);
        $this->client = $this->makeClient();
        Coroutine::create(function ()
        {
            $reason = '';
            try {
                $chan   = $this->chan;
                $client = $this->client;
                while (true) {
                    //TODO 待优化
                    $data = $client->recv(-1);
                    if (!$client->isConnected()) {
                        $reason = 'client disconnected. ' . $client->errMsg;
                        break;
                    }
                    if ($chan->isClosing()) {
                        $reason = 'channel closed.';
                        break;
                    }

                    if ($data === false || $data === '') {
                        $reason = 'client broken. ' . $client->errMsg;
                        break;
                    }

                    $packet = $this->packer->unpack($data);
                    if ($packet->isHeartbeat()) {
                        continue;
                    }

                    if ($channel = $this->getChannelManager()->get($packet->getId())) {
                        $channel->push(
                            $this->serializer->unserialize($packet->getBody())
                        );
                    } else {
                        $this->logger && $this->logger->error(sprintf('Recv channel [%d] does not exists.', $packet->getId()));
                    }
                }
            }
            finally {
                $this->logger && $this->logger->warning('Recv loop broken, wait to restart in next time. The reason is ' . $reason);
                $chan->close();
                $client->close();
            }
        });

        Coroutine::create(function ()
        {
            $reason = '';
            try {
                $chan   = $this->chan;
                $client = $this->client;
                while (true) {
                    $data = $chan->pop();
                    if ($chan->isClosing()) {
                        $reason = 'channel closed.';
                        break;
                    }
                    if (!$client->isConnected()) {
                        $reason = 'client disconnected.' . $client->errMsg;
                        break;
                    }

                    if (empty($data)) {
                        continue;
                    }

                    $res = $client->send($data);
                    if ($res === false) {
                        $this->logger && $this->logger->warning('Send data failed. The reason is ' . $client->errMsg);
                    }
                }
            }
            finally {
                $this->logger && $this->logger->warning('Send loop broken, wait to restart in next time. The reason is ' . $reason);
                $chan->close();
                $client->close();
            }
        });
    }
}
