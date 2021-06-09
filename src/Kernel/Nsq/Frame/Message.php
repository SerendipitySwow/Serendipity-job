<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq\Frame;

use Serendipity\Job\Kernel\Nsq\Frame;

final class Message extends Frame
{
    public function __construct(
        public int $timestamp,
        public int $attempts,
        public string $id,
        public string $body,
    ) {
        parent::__construct(self::TYPE_MESSAGE);
    }
}
