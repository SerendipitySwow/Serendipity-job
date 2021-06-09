<?php
declare( strict_types = 1 );

namespace Serendipity\Job\Event;

use Serendipity\Job\Contract\EventDispatcherInterface;

class EventDispatcher extends \Symfony\Component\EventDispatcher\EventDispatcher implements EventDispatcherInterface
{
    public function dispatch (object $event, string $eventName = null): object
    {
    }
}
