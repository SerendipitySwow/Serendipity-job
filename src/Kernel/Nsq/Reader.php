<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

use Amp\Deferred;
use Amp\Promise;
use Amp\Success;
use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Kernel\Nsq\Config\ClientConfig;
use Serendipity\Job\Kernel\Nsq\Exception\ConsumerException;
use Serendipity\Job\Kernel\Nsq\Frame\Response;
use Serendipity\Job\Kernel\Nsq\Stream\NullStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Amp\asyncCall;
use function Amp\call;

final class Reader extends Connection implements ConsumerInterface
{
    private int $rdy = 0;

    /**
     * @var array<int, Deferred<Message>>
     */
    private array $deferreds = [];

    /**
     * @var array<int, Message>
     */
    private array $messages = [];

    #[Pure]
    public function __construct(
        private string $address,
        private string $topic,
        private string $channel,
        ClientConfig $clientConfig,
        private LoggerInterface $logger,
    ) {
        parent::__construct(
            $this->address,
            $clientConfig,
            $this->logger,
        );
    }

    public static function create(
        string $address,
        string $topic,
        string $channel,
        ?ClientConfig $clientConfig = null,
        ?LoggerInterface $logger = null,
    ): self {
        return new self(
            $address,
            $topic,
            $channel,
            $clientConfig ?? new ClientConfig(),
            $logger ?? new NullLogger(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): Promise
    {
        if (!$this->stream instanceof NullStream) {
            return call(static function (): void {
            });
        }

        return call(function (): \Generator {
            yield parent::connect();

            $this->run();
        });
    }

    private function run(): void
    {
        $buffer = new Buffer();

        asyncCall(function () use ($buffer): \Generator {
            yield $this->stream->write(Command::sub($this->topic, $this->channel));

            if (null !== ($chunk = yield $this->stream->read())) {
                $buffer->append($chunk);
            }

            /** @var Response $response */
            $response = Parser::parse($buffer);

            if (!$response->isOk()) {
                throw new ConsumerException('Fail subscription.');
            }

            yield $this->rdy(1);

            asyncCall(
                function () use ($buffer): \Generator {
                    while (null !== $chunk = yield $this->stream->read()) {
                        $buffer->append($chunk);

                        while ($frame = Parser::parse($buffer)) {
                            switch (true) {
                                case $frame instanceof Serendipity\Job\Kernel\Nsq\Frame\Response:
                                    if ($frame->isHeartBeat()) {
                                        yield $this->stream->write(Command::nop());

                                        break;
                                    }

                                    throw ConsumerException::response($frame);
                                case $frame instanceof Serendipity\Job\Kernel\Nsq\Frame\Error:
                                    $this->handleError($frame);

                                    $deferred = array_pop($this->deferreds);

                                    if (null !== $deferred) {
                                        $deferred->fail($frame->toException());
                                    }

                                    break;
                                case $frame instanceof Serendipity\Job\Kernel\Nsq\Frame\Message:
                                    $message = Message::compose($frame, $this);

                                    $deferred = array_pop($this->deferreds);

                                    if (null === $deferred) {
                                        $this->messages[] = $message;
                                    } else {
                                        $deferred->resolve($message);
                                    }

                                    break;
                            }
                        }
                    }

                    $this->stream = new NullStream();
                }
            );
        });
    }

    /**
     * @return Promise<Message>
     */
    public function consume(): Promise
    {
        $message = array_pop($this->messages);

        if (null !== $message) {
            return new Success($message);
        }

        $this->deferreds[] = $deferred = new Deferred();

        return $deferred->promise();
    }

    /**
     * Update RDY state (indicate you are ready to receive N messages).
     *
     * @return Promise<void>
     */
    public function rdy(int $count): Promise
    {
        if ($this->rdy === $count) {
            return call(static function (): void {
            });
        }

        $this->rdy = $count;

        return $this->stream->write(Command::rdy($count));
    }

    /**
     * Finish a message (indicate successful processing).
     *
     * @return Promise<void>
     *
     * @internal
     */
    public function fin(string $id): Promise
    {
        --$this->rdy;

        return $this->stream->write(Command::fin($id));
    }

    /**
     * Re-queue a message (indicate failure to process) The re-queued message is placed at the tail of the queue,
     * equivalent to having just published it, but for various implementation specific reasons that behavior should not
     * be explicitly relied upon and may change in the future. Similarly, a message that is in-flight and times out
     * behaves identically to an explicit REQ.
     *
     * @return Promise<void>
     *
     * @internal
     */
    public function req(string $id, int $timeout): Promise
    {
        --$this->rdy;

        return $this->stream->write(Command::req($id, $timeout));
    }

    /**
     * Reset the timeout for an in-flight message.
     *
     * @return Promise<void>
     *
     * @internal
     */
    public function touch(string $id): Promise
    {
        return $this->stream->write(Command::touch($id));
    }
}
