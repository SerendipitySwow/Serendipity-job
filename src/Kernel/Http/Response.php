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
        $this->withJsonResponseHeader();
        $json = json_encode($data, $flags, $depth);
        $this->response($json);

        return $this;
    }

    public function text(string $text): Response
    {
        $this->withTextResponseHeader();
        $this->response($text);

        return $this;
    }

    protected function response(string $str): void
    {
        $this->makeBuffer(strlen($str));
        $this->writeBuffer($str);
        $this->setBody($this->buffer);
    }

    protected function withTextResponseHeader(): void
    {
        $this->setResponseServerHeader();
        $this->setHeader('Content-Type', 'Content-Type: text/html; charset=utf-8');
    }

    private function setResponseServerHeader(): void
    {
        $this->setHeader('Server', 'Serendipity-Job');
    }

    protected function withJsonResponseHeader(): void
    {
        $this->setResponseServerHeader();
        $this->setHeader('content-type', 'application/json; charset=utf-8');
    }

    protected function makeBuffer(int $length = \Swow\Buffer::DEFAULT_SIZE): void
    {
        $this->buffer = $this->buffer ?? make(Buffer::class, ['size' => $length]);
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
