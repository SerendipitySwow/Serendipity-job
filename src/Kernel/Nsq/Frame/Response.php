<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq\Frame;

use Serendipity\Job\Kernel\Nsq\Frame;

/**
 * @psalm-immutable
 */
final class Response extends Frame
{
    public const OK = 'OK';
    public const HEARTBEAT = '_heartbeat_';

    public function __construct(public string $data)
    {
        parent::__construct(self::TYPE_RESPONSE);
    }

    public function isOk(): bool
    {
        return self::OK === $this->data;
    }

    public function isHeartBeat(): bool
    {
        return self::HEARTBEAT === $this->data;
    }

    /**
     * @return array<mixed, mixed>
     */
    public function toArray(): array
    {
        return json_decode($this->data, true, flags: JSON_THROW_ON_ERROR);
    }
}
