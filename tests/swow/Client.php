<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Swow\Buffer;
use Swow\Socket;

class Client
{
    protected string $host = '127.0.0.1';

    protected int $port = 9764;

    protected int $timeout = 0;

    protected Socket $client;

    protected bool $closed = false;

    protected Buffer $buffer;

    protected ?string $recvBuffer = null;

    /**
     * EOF
     */
    public const EOF = "\r\n";

    public function __construct(string $host, int $port, int $timeout)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $client = new Socket(Socket::TYPE_TCP);
        $client->connect($host, $port, $timeout);
        $this->client = $client;
    }

    public function send(string $message)
    {
        $buffer = new Swow\Buffer();
        $buffer->write($message);

        return $this->client->sendString($message);
    }

    public function recv(): string|bool
    {
        $swowBuffer = new Swow\Buffer();
        $length = $this->client->recv($swowBuffer);
        if ($length === 0) {
            return false;
        }
        $offset = 0;
        while (true) {
            $buffer = $swowBuffer->toString();
            var_dump(11111, $buffer);
            echo "END\r\n";
            $eof = strpos($buffer, static::EOF, $offset);
            if ($eof === false) {
                break;
            }
            $recvLength = $eof + strlen(static::EOF);
            $peek = $swowBuffer->truncate($offset, $recvLength)->read();
            var_dump($peek);
            echo "PEEK\r\n";
            if ($recvLength === $length) {
                $swowBuffer->clear();
                break;
            }  /* < */
            $offset += $recvLength;
        }

        return $this->recvBuffer;
    }

    public function close(): void
    {
        $this->client->close() && $this->closed = true;
    }
}
require_once(dirname(__DIR__)) . '/../vendor/autoload.php';
\Swow\Debug\Debugger::runOnTTY();
try {
    for ($j = 0; $j < 10; $j++) {
        $client = new \SwowCloud\RedisSubscriber\Connection('127.0.0.1', 6379, -1);
        $client->sendCommand(['PUBLISH', 'second', 'Hello']);
        var_dump($msg = $client->read());
    }
    for ($i = 0; $i < 10; $i++) {
        \Swow\Coroutine::run(function () {
            $client = new \SwowCloud\RedisSubscriber\Connection('127.0.0.1', 6379, -1);
            //            $client->sendCommand(['sadd', "listA", (string)mt_rand(10000,9999999)]);
            $client->sendCommand(['SUBSCRIBE', 'second', 'hello']);
            //            var_dump($msg = $client->read());
            var_dump($msg = $client->read());
        });
    }
} catch (Throwable $e) {
    var_dump($e);
}
