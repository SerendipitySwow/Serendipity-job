<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Kernel\Nsq\Exception;

use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Kernel\Nsq\Frame\Response;

final class ConsumerException extends NsqException
{
    #[Pure]
    public static function response (
        Response $response
    ): self {
        return new self(sprintf('Consumer receive response [%s] from nsq, which not expected. ', $response->data));
    }
}
