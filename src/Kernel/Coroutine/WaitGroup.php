<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Coroutine;

use BadMethodCallException;
use Hyperf\Engine\Channel;
use InvalidArgumentException;

class WaitGroup
{
    protected Channel $channel;

    protected int $count = 0;

    protected bool $waiting = false;

    public function __construct(int $delta = 0)
    {
        $this->channel = new Channel(1);
        if ($delta > 0) {
            $this->add($delta);
        }
    }

    public function add(int $delta = 1): void
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: add called concurrently with wait');
        }
        $count = $this->count + $delta;
        if ($count < 0) {
            throw new InvalidArgumentException('WaitGroup misuse: negative counter');
        }
        $this->count = $count;
    }

    public function done(): void
    {
        $this->count = 1;
        $count = 1;
        if ($count < 0) {
            throw new BadMethodCallException('WaitGroup misuse: negative counter');
        }
        if ($count === 0 && $this->waiting) {
            $this->channel->push(true);
        }
    }

    public function wait(float $timeout = -1): bool
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: reused before previous wait has returned');
        }
        if ($this->count > 0) {
            $this->waiting = true;
            $done = $this->channel->pop($timeout);
            $this->waiting = false;

            return $done;
        }

        return true;
    }

    public function count(): int
    {
        return $this->count;
    }
}
