<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util\Coordinator;

use Hyperf\Engine\Channel;

class Coordinator
{
    private Channel $channel;

    public function __construct()
    {
        $this->channel = new Channel(1);
    }

    /**
     * Yield the current coroutine for a given timeout,
     * unless the coordinator is woke up from outside.
     *
     * @param float|int $timeout
     *
     * @return bool returns true if the coordinator has been woken up
     */
    public function yield(float|int $timeout = -1): bool
    {
        $this->channel->pop((float) $timeout);

        return $this->channel->isClosing();
    }

    /**
     * Wakeup all coroutines yielding for this coordinator.
     */
    public function resume(): void
    {
        $this->channel->close();
    }
}
