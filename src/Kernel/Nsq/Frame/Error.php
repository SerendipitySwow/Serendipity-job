<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq\Frame;

use Serendipity\Job\Kernel\Nsq\Exception\ServerException;
use Serendipity\Job\Kernel\Nsq\Frame;

/**
 * @psalm-immutable
 */
final class Error extends Frame
{
    public function __construct(public string $data)
    {
        parent::__construct(self::TYPE_ERROR);
    }

    public function toException(): ServerException
    {
        return new ServerException($this->data);
    }
}
