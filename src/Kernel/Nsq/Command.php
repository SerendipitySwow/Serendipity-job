<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

use PHPinnacle\Buffer\ByteBuffer;

/**
 * @internal
 */
final class Command
{
    public static function magic(): string
    {
        return '  V2';
    }

    public static function identify(string $data): string
    {
        return self::pack('IDENTIFY', data: $data);
    }

    public static function auth(?string $authSecret): string
    {
        return self::pack('AUTH', data: $authSecret);
    }

    public static function nop(): string
    {
        return self::pack('NOP');
    }

    public static function cls(): string
    {
        return self::pack('CLS');
    }

    public static function rdy(int $count): string
    {
        return self::pack('RDY', (string) $count);
    }

    public static function fin(string $id): string
    {
        return self::pack('FIN', $id);
    }

    public static function req(string $id, int $timeout): string
    {
        return self::pack('REQ', [$id, $timeout]);
    }

    public static function touch(string $id): string
    {
        return self::pack('TOUCH', $id);
    }

    public static function pub(string $topic, string $body): string
    {
        return self::pack('PUB', $topic, $body);
    }

    /**
     * @param array<int, string> $bodies
     */
    public static function mpub(string $topic, array $bodies): string
    {
        static $buffer;
        $buffer ??= new ByteBuffer();

        $buffer->appendUint32(\count($bodies));

        foreach ($bodies as $body) {
            $buffer->appendUint32(\strlen($body));
            $buffer->append($body);
        }

        return self::pack('MPUB', $topic, $buffer->flush());
    }

    public static function dpub(string $topic, string $body, int $delay): string
    {
        return self::pack('DPUB', [$topic, $delay], $body);
    }

    public static function sub(string $topic, string $channel): string
    {
        return self::pack('SUB', [$topic, $channel]);
    }

    /**
     * @param array<int, scalar>|string $params
     */
    private static function pack(string $command, array | string $params = [], string $data = null): string
    {
        static $buffer;
        $buffer ??= new Buffer();

        $command = implode(' ', [$command, ...((array) $params)]);

        $buffer->append($command.PHP_EOL);

        if (null !== $data) {
            $buffer->appendUint32(\strlen($data));
            $buffer->append($data);
        }

        return $buffer->flush();
    }
}
