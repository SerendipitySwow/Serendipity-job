<?php

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Nsq;

use Amp\Promise;

interface ConsumerInterface
{
    /**
     * Update RDY state (indicate you are ready to receive N messages).
     *
     * @return Promise<void>
     */
    public function rdy(int $count): Promise;

    /**
     * Finish a message (indicate successful processing).
     *
     * @return Promise<void>
     *
     * @internal
     */
    public function fin(string $id): Promise;

    /**
     * Re-queue a message (indicate failure to process) The re-queued message is placed at the tail of the queue,
     * equivalent to having just published it, but for various implementation specific reasons that behavior should not
     * be explicitly relied upon and may change in the future. Similarly, a message that is in-flight and times out
     * behaves identically to an explicit REQ.
     *
     * @return Promise<void>
     *
     * @internal
     */
    public function req(string $id, int $timeout): Promise;

    /**
     * Reset the timeout for an in-flight message.
     *
     * @return Promise<void>
     *
     * @internal
     */
    public function touch(string $id): Promise;
}
