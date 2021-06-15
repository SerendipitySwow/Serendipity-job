<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\IOMultiplex;

use Exception;
use Hyperf\Engine\Channel;
use Multiplex\ChannelManager;
use Multiplex\Constract\ClientInterface;
use Multiplex\Constract\HasSerializerInterface;
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
use SerendipitySwow\Socket\Exceptions\OpenStreamException;
use SerendipitySwow\Socket\Exceptions\StreamStateException;
use SerendipitySwow\Socket\Streams\Socket;
use Throwable;

class Client implements ClientInterface, HasSerializerInterface
{
    protected string $name;

    protected int $port;

    protected Packer $packer;

    protected SerializerInterface $serializer;

    protected IdGeneratorInterface $generator;

    /**
     * @var ?Channel
     */
    protected ?Channel $chan;

    protected ?Socket $client;

    protected Collection $config;

    protected ChannelManager $channelManager;

    protected bool $heartbeat = false;

    protected ?LoggerInterface $logger;

    public function __construct(
        string $name,
        int $port,
        ?IdGeneratorInterface $generator = null,
        ?SerializerInterface $serializer = null,
        ?PackerInterface $packer = null
    ) {
        $this->name = $name;
        $this->port = $port;
        $this->packer = $packer ?? new Packer();
        $this->generator = $generator ?? new IdGenerator();
        $this->serializer = $serializer ?? new StringSerializer();
        $this->channelManager = new ChannelManager();
        $this->config = new Collection([
            'recv_timeout' => 10,
            'max_length' => 2 * 1024 * 1024,
            'connect_timeout' => 5,
            // 'heartbeat' => null,
        ]);
    }

    public function set(array $settings): Client | static
    {
        $this->config = new Collection($settings);

        return $this;
    }

    public function request($data)
    {
        return $this->recv($this->send($data));
    }

    public function send($data): int
    {
        $this->loop();

        $this->getChannelManager()
            ->get($id = $this->generator->generate(), true);

        try {
            $payload = $this->packer->pack(
                new Packet(
                    $id,
                    $this->getSerializer()
                        ->serialize($data)
                )
            );

            $this->chan->push($payload);
        } catch (Throwable $exception) {
            is_int($id) && $this->getChannelManager()
                ->close($id);
            throw $exception;
        }

        return $id;
    }

    public function recv(int $id)
    {
        $this->loop();

        $manager = $this->getChannelManager();
        $chan = $manager->get($id);
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
        } finally {
            $manager->close($id);
        }

        return $data;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function getChannelManager(): ChannelManager
    {
        return $this->channelManager;
    }

    public function close(): void
    {
        $this->client && $this->client->close();
        $this->chan && $this->chan->close();
    }

    public function setLogger(?LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    protected function makeClient(): Socket
    {
        $client = new Socket($this->name, $this->port, $this->config->get('connect_timeout'));
        try {
            $client->open();
        } catch (Exception | OpenStreamException $e) {
            $this->close();
            $this->logger->error($e->getMessage());
        }

        return $client;
    }

    protected function heartbeat(): void
    {
        $heartbeat = $this->config->get('heartbeat');
        if (!$this->heartbeat && is_numeric($heartbeat)) {
            $this->heartbeat = true;

            Coroutine::create(function () use ($heartbeat) {
                while (true) {
                    ## TODO
                    /*
                    if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield($heartbeat)) {
                        break;
                    }
                    */

                    try {
                        // PING
                        if ($chan = $this->chan and $chan->isEmpty()) {
                            $payload = $this->packer->pack(
                                new Packet(0, Packet::PING)
                            );
                            $chan->push($payload);
                        }
                    } catch (Throwable $exception) {
                        $this->logger && $this->logger->error((string) $exception);
                    }
                }
            });
        }
    }

    protected function loop(): void
    {
        $this->heartbeat();

        if ($this->chan !== null && !$this->chan->isClosing()) {
            return;
        }
        $this->chan = $this->getChannelManager()
            ->make(65535);
        $this->client = $this->makeClient();
        Coroutine::create(function () {
            $reason = '';
            try {
                $chan = $this->chan;
                $client = $this->client;
                while (true) {
                    $data = $client->readChar();
                    if ($chan->isClosing()) {
                        $reason = 'channel closed.';
                        break;
                    }

                    if ($data === null || $data === '') {
                        $reason = 'client broken. ' . error_get_last();
                        break;
                    }

                    $packet = $this->packer->unpack($data);
                    if ($packet->isHeartbeat()) {
                        continue;
                    }

                    if ($channel = $this->getChannelManager()
                        ->get($packet->getId())) {
                        $channel->push(
                            $this->serializer->unserialize($packet->getBody())
                        );
                    } else {
                        $this->logger && $this->logger->error(sprintf(
                            'Recv channel [%d] does not exists.',
                            $packet->getId()
                        ));
                    }
                }
            } catch (StreamStateException $exception) {
                $this->logger && $this->logger->error(sprintf('Recv error [%s]#', $exception->getMessage()));
            } finally {
                $this->logger && $this->logger->warning('Recv loop broken, wait to restart in next time. The reason is ' . $reason);
                $chan->close();
                $client->close();
            }
        });

        Coroutine::create(function () {
            $reason = '';
            try {
                $chan = $this->chan;
                $client = $this->client;
                while (true) {
                    $data = $chan->pop();
                    if ($chan->isClosing()) {
                        $reason = 'channel closed.';
                        break;
                    }
                    if (!$client->isOpen()) {
                        $reason = 'client disconnected.' . error_get_last();
                        break;
                    }

                    if (empty($data)) {
                        continue;
                    }

                    $res = $client->write($data);
                    if ($res) {
                        $this->logger && $this->logger->warning('Send data failed. The reason is ' . error_get_last());
                    }
                }
            } catch (StreamStateException | OpenStreamException $e) {
                $this->logger && $this->logger->warning($e->getMessage());
            } finally {
                $this->logger && $this->logger->warning('Send loop broken, wait to restart in next time. The reason is ' . $reason);
                $chan->close();
                $client->close();
            }
        });
    }
}
