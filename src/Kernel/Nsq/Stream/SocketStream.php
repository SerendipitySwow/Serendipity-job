<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq\Stream;

use Amp\Promise;
use Amp\Socket\ConnectContext;
use Amp\Socket\Socket;
use Serendipity\Job\Kernel\Nsq\Stream;
use function Amp\call;
use function Amp\Socket\connect;

class SocketStream implements Stream
{
    public function __construct(private Socket $socket)
    {
    }

    /**
     * @return Promise<self>
     */
    public static function connect(string $uri, int $timeout = 0, int $attempts = 0, bool $noDelay = false): Promise
    {
        return call(function () use ($uri, $timeout, $attempts, $noDelay): \Generator {
            $context = new ConnectContext();

            if ($timeout > 0) {
                $context = $context->withConnectTimeout($timeout);
            }

            if ($attempts > 0) {
                $context = $context->withMaxAttempts($attempts);
            }

            if ($noDelay) {
                $context = $context->withTcpNoDelay();
            }

            return new self(yield connect($uri, $context));
        });
    }

    /**
     * @return Promise<null|string>
     */
    public function read(): Promise
    {
        return $this->socket->read();
    }

    /**
     * @return Promise<void>
     */
    public function write(string $data): Promise
    {
        return $this->socket->write($data);
    }

    public function close(): void
    {
        $this->socket->close();
    }
}
