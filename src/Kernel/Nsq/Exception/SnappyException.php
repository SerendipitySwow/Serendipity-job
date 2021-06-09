<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Kernel\Nsq\Exception;

use JetBrains\PhpStorm\Pure;

final class SnappyException extends NsqException
{
    #[Pure]
    public static function notInstalled (): self
    {
        return new self('Snappy extension not installed.');
    }

    #[Pure]
    public static function invalidHeader (): self
    {
        return new self('Invalid snappy protocol header.');
    }
}
