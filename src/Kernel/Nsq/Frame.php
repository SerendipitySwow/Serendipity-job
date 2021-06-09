<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

abstract class Frame
{
    public const TYPE_RESPONSE = 0,
        TYPE_ERROR = 1,
        TYPE_MESSAGE = 2
    ;

    public function __construct(public int $type)
    {
    }

    public function response(): bool
    {
        return self::TYPE_RESPONSE === $this->type;
    }

    public function error(): bool
    {
        return self::TYPE_ERROR === $this->type;
    }

    public function message(): bool
    {
        return self::TYPE_MESSAGE === $this->type;
    }
}
