<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

class Socket1
{
    public $socket;

    public function check()
    {
        return (bool) $this->socket?->ischeck();
    }
}
