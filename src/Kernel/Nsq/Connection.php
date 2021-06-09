<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

use Amp\Promise;
use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Kernel\Nsq\Config\ClientConfig;
use Serendipity\Job\Kernel\Nsq\Config\ConnectionConfig;
use Serendipity\Job\Kernel\Nsq\Exception\AuthenticationRequired;
use Serendipity\Job\Kernel\Nsq\Exception\NsqException;
use Serendipity\Job\Kernel\Nsq\Frame\Response;
use Serendipity\Job\Kernel\Nsq\Stream\GzipStream;
use Serendipity\Job\Kernel\Nsq\Stream\NullStream;
use Serendipity\Job\Kernel\Nsq\Stream\SnappyStream;
use Serendipity\Job\Kernel\Nsq\Stream\SocketStream;
use Psr\Log\LoggerInterface;
use function Amp\call;

/**
 * @internal
 */
abstract class Connection
{
    protected Stream $stream;

    #[Pure]
    public function __construct(
        private string $address,
        private ClientConfig $clientConfig,
        private LoggerInterface $logger,
    ) {
        $this->stream = new NullStream();
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return Promise<void>
     */
    public function connect(): Promise
    {
        return call(function (): \Generator {
            $buffer = new Buffer();

            /** @var SocketStream $stream */
            $stream = yield SocketStream::connect($this->address);

            yield $stream->write(Command::magic());
            yield $stream->write(Command::identify($this->clientConfig->toString()));

            /** @var Response $response */
            $response = yield $this->response($stream, $buffer);
            $connectionConfig = ConnectionConfig::fromArray($response->toArray());

            if ($connectionConfig->snappy) {
                $stream = new SnappyStream($stream, $buffer->flush());

                /** @var Response $response */
                $response = yield $this->response($stream, $buffer);

                if (!$response->isOk()) {
                    throw new NsqException();
                }
            }

            if ($connectionConfig->deflate) {
                $stream = new GzipStream($stream);

                /** @var Response $response */
                $response = yield $this->response($stream, $buffer);

                if (!$response->isOk()) {
                    throw new NsqException();
                }
            }

            if ($connectionConfig->authRequired) {
                if (null === $this->clientConfig->authSecret) {
                    throw new AuthenticationRequired();
                }

                yield $stream->write(Command::auth($this->clientConfig->authSecret));

                /** @var Response $response */
                $response = yield $this->response($stream, $buffer);

                $this->logger->info('Authorization response: '.http_build_query($response->toArray()));
            }

            $this->stream = $stream;
        });
    }

    public function close(): void
    {
//        $this->stream->write(Command::cls());

        $this->stream->close();
        $this->stream = new NullStream();
    }

    protected function handleError(Serendipity\Job\Kernel\Nsq\Frame\Error $error): void
    {
        $this->logger->error($error->data);

        if (ErrorType::terminable($error)) {
            $this->close();

            throw $error->toException();
        }
    }

    /**
     * @return Promise<Serendipity\Job\Kernel\Nsq\Frame\Response>
     */
    private function response(Stream $stream, Buffer $buffer): Promise
    {
        return call(function () use ($stream, $buffer): \Generator {
            while (true) {
                $response = Parser::parse($buffer);

                if (null === $response && null !== ($chunk = yield $stream->read())) {
                    $buffer->append($chunk);

                    continue;
                }

                if (!$response instanceof Serendipity\Job\Kernel\Nsq\Frame\Response) {
                    throw new NsqException();
                }

                return $response;
            }
        });
    }
}
