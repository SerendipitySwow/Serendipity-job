<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq\Stream;

use Amp\Promise;
use Serendipity\Job\Kernel\Nsq\Exception\NsqException;
use Serendipity\Job\Kernel\Nsq\Stream;

class GzipStream implements Stream
{
    public function __construct(private Stream $stream)
    {
        throw new NsqException('GzipStream not implemented yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function read(): Promise
    {
        return $this->stream->read();
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data): Promise
    {
        return $this->stream->write($data);
    }

    public function close(): void
    {
        $this->stream->close();
    }
}
