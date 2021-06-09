<?php
declare( strict_types = 1 );

namespace Serendipity\Job\Contract;

interface EventDispatcherInterface
{
    public function dispatch (object $event, string $eventName = null): object;
}
