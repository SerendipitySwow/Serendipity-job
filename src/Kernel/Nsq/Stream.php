<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

use Amp\Promise;

interface Stream
{
    /**
     * @return Promise<null|string>
     */
    public function read(): Promise;

    /**
     * @return Promise<void>
     */
    public function write(string $data): Promise;

    public function close(): void;
}
