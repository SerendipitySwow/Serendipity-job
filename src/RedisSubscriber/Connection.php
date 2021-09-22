<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\RedisSubscriber;

use InvalidArgumentException;
use Serendipity\Job\Redis\Exception\InvalidRedisConnectionException;
use Serendipity\Job\RedisSubscriber\Error as ErrorResponse;
use Serendipity\Job\RedisSubscriber\Status as StatusResponse;
use Swow\Buffer;
use Swow\Socket;

/**
 * @see https://cloud.tencent.com/developer/article/1556440 Redis通信协议用php实现
 */
class Connection
{
    protected string $host = '';

    protected int $port = 6379;

    protected int $timeout = 0;

    protected Socket $client;

    protected bool $closed = false;

    protected Buffer $buffer;

    protected ?string $recvBuffer;

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

    public function send(string $message): void
    {
        $this->client->sendString($message, strlen($message));
    }

    /**
     * 1:"+" 状态回复（status reply）
     * 2:"-" 错误回复（error reply）
     * 3:":" 整数回复（integer reply）
     * 4:"$" 批量回复（bulk reply）
     * 5:"*" 多条批量回复（multi bulk reply）
     * @return array|bool|\Serendipity\Job\RedisSubscriber\Error|\Serendipity\Job\RedisSubscriber\Status|string
     */
    public function read(): Status|bool|array|string|Error
    {
        $chunk = $this->client->recvString();
        if ($chunk === '') {
            throw new InvalidRedisConnectionException('Error while reading line from the server.');
        }
        $prefix = $chunk[0];
        $payload = substr($chunk, 1, -2);

        switch ($prefix) {
            case '+':
                return StatusResponse::get($payload);
            case '$':
                $size = (int) $payload;

                if ($size === -1) {
                    return false;
                }

                $bulkData = '';
                $bytesLeft = ($size += 2);

                do {
                    $chunk = $this->client->recvString(min($bytesLeft, 4096));

                    if ($chunk === '') {
                        throw new InvalidRedisConnectionException('Error while reading bytes from the server.');
                    }

                    $bulkData .= $chunk;
                    $bytesLeft = $size - strlen($bulkData);
                } while ($bytesLeft > 0);

                return substr($bulkData, 0, -2);
            case '*':
                $count = (int) $payload;

                if ($count === -1) {
                    return false;
                }

                $multibulk = [];

                for ($i = 0; $i < $count; ++$i) {
                    $multibulk[$i] = $this->read();
                }

                return $multibulk;
            case ':':
                $integer = (int) $payload;

                return $payload;
            case '-':
                return new ErrorResponse($payload);
            default:
                throw new InvalidArgumentException("Unknown response prefix: '{$prefix}'.");
        }
    }

    public function close(): void
    {
        $this->client->close() && $this->closed = true;
    }

    public function sendCommand(array $commandList): void
    {
        $argNum = count($commandList);
        $str = "*{$argNum}\r\n";
        foreach ($commandList as $value) {
            $len = strlen($value);
            $str .= '$' . "{$len}\r\n{$value}\r\n";
        }
        $this->send($str);
    }
}
