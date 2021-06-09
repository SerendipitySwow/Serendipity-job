<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Kernel\Nsq\Exception;

use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Kernel\Nsq\Message;

final class MessageException extends NsqException
{
    #[Pure]
    public static function processed (
        Message $message
    ): self {
        return new self(sprintf('Message "%s" already processed.', $message->id));
    }
}
