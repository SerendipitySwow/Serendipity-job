<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

final class Bytes
{
    public const BYTES_SIZE = 4;
    public const BYTES_TYPE = 4;
    public const BYTES_ATTEMPTS = 2;
    public const BYTES_TIMESTAMP = 8;
    public const BYTES_ID = 16;
}
