<?php
declare(strict_types = 1);
namespace Serendipity\Job\Kernel\Socket\Streams;

use Serendipity\Job\Kernel\Socket\Exceptions\OpenStreamException;
use Serendipity\Job\Kernel\Socket\Exceptions\StreamStateException;
use Serendipity\Job\Kernel\Socket\Exceptions\WriteStreamException;
use Serendipity\Job\Kernel\Socket\Interfaces\StreamInterface;
use function error_get_last;
use function fclose;
use function fwrite;
use function is_resource;
use function stream_set_timeout;
use function strlen;

final class Socket implements StreamInterface
{
    /**
     * Default connection timeout in seconds.
     */
    public const DEFAULT_CONNECTION_TIMEOUT = 2;

    /**
     * @var string Hostname/IP
     */
    private string $host;

    /**
     * @var int TCP port
     */
    private int $port;

    /**
     * @var float Connection timeout
     */
    private float $connectionTimeout;

    /**
     * @var resource
     */
    private $socket;

    /**
     * Create a TCP socket.
     *
     * @param string     $host    The hostname.
     * @param int        $port    The port number.
     * @param float|null $timeout The optional connection timeout, in seconds.
     */
    public function __construct(string $host, int $port, float $timeout = null)
    {
        $this->host              = $host;
        $this->port              = $port;
        $this->connectionTimeout = $timeout ? : self::DEFAULT_CONNECTION_TIMEOUT;
    }

    /**
     * Socket destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function isOpen() : bool
    {
        return is_resource($this->socket);
    }

    /**
     * @inheritDoc
     */
    public function open() : void
    {
        if ($this->isOpen()) {
            throw new StreamStateException('Stream already opened.');
        }
        $socket = @stream_socket_client(sprintf('tcp://%s:%s', $this->host, $this->port), $errno, $errstr, 1);
        if (!is_resource($socket)) {
            throw new OpenStreamException($errstr, $errno);
        }
        $this->setTimeout($this->connectionTimeout, 5);
        $this->socket = $socket;
    }

    /**
     * @inheritDoc
     */
    public function close() : void
    {
        if ($this->isOpen()) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function write(string $string) : int
    {
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        $length = strlen($string);
        $bytes  = fwrite($this->socket, $string, $length);
        if ($bytes === false) {
            throw new WriteStreamException(error_get_last());
        }
        return $bytes;
    }

    /**
     * @inheritDoc
     */
    public function readChar() : ?string
    {
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        $char = fread($this->socket,1024);
        if ($char === false) {
            return null;
        }
        return $char;
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(int $seconds, int $microseconds) : bool
    {
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        return stream_set_timeout($this->socket, $seconds, $microseconds);
    }

    /**
     * @inheritDoc
     */
    public function timedOut() : bool
    {
        if (!$this->isOpen()) {
            throw new StreamStateException('Stream not opened.');
        }
        $metadata = stream_get_meta_data($this->socket);
        return (bool)$metadata['timed_out'];
    }
}
