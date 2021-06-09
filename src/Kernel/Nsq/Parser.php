<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

use Serendipity\Job\Kernel\Nsq\Exception\NsqException;

class Parser
{
    private const SIZE = 4;
    private const TYPE = 4;
    private const MESSAGE_HEADER_SIZE =
        8 +  // timestamp
        2 +  // attempts
        16 + // ID
        4;   // Frame type

    public static function parse(Buffer $buffer): ?Frame
    {
        if ($buffer->size() < self::SIZE) {
            return null;
        }

        $size = $buffer->readInt32();

        if ($buffer->size() < $size + self::SIZE) {
            return null;
        }

        $buffer->discard(self::SIZE);

        $type = $buffer->consumeInt32();

        return match ($type) {
            Frame::TYPE_RESPONSE => new Serendipity\Job\Kernel\Nsq\Frame\Response($buffer->consume($size - self::TYPE)),
            Frame::TYPE_ERROR => new Serendipity\Job\Kernel\Nsq\Frame\Error($buffer->consume($size - self::TYPE)),
            Frame::TYPE_MESSAGE => new Serendipity\Job\Kernel\Nsq\Frame\Message(
                timestamp: $buffer->consumeTimestamp(),
                attempts: $buffer->consumeAttempts(),
                id: $buffer->consumeMessageID(),
                body: $buffer->consume($size - self::MESSAGE_HEADER_SIZE),
            ),
            default => throw new NsqException(sprintf('Unexpected frame type: "%s"', $type)),
        };
    }
}
