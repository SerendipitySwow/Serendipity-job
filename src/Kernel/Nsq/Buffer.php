<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

use PHPinnacle\Buffer\ByteBuffer;

/**
 * @psalm-suppress
 */
final class Buffer extends ByteBuffer
{
    public function readUInt32LE(): int
    {
        /** @phpstan-ignore-next-line  */
        return unpack('V', $this->consume(4))[1];
    }

    public function consumeTimestamp(): int
    {
        return $this->consumeUint64();
    }

    public function consumeAttempts(): int
    {
        return $this->consumeUint16();
    }

    public function consumeMessageID(): string
    {
        return $this->consume(16);
    }
}
