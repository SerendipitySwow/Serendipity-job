<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Http;

use RuntimeException;
use Swow\Http\Buffer;
use Swow\Http\Server\Response as SwowResponse;

class Response extends SwowResponse
{
    protected ?Buffer $buffer = null;

    public function json(array $data, int $flags = JSON_THROW_ON_ERROR, int $depth = 512): self
    {
        $this->handleJsonResponseHeader();
        $json = json_encode($data, $flags, $depth);
        $this->makeBuffer(strlen($json));
        $this->writeBuffer($json);
        $this->setBody($this->buffer);

        return $this;
    }

    protected function handleJsonResponseHeader(): void
    {
        $this->setHeader('Server', 'Serendipity-Job')
            ->setHeader('content-type', 'application/json; charset=utf-8');
    }

    protected function makeBuffer(int $length = \Swow\Buffer::DEFAULT_SIZE): void
    {
        $this->buffer = make(Buffer::class, ['size' => $length]);
    }

    protected function writeBuffer(string $str = null): void
    {
        if ($this->buffer->isFull()) {
            throw new RuntimeException('Http Buffer Is Full#');
        }
        $this->buffer->write($str);
    }

    public function __destruct()
    {
        $this->buffer = null;
    }
}
