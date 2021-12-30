<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Contract;

interface EventDispatcherInterface
{
    public function dispatch(object $event, string $eventName = null): object;
}
