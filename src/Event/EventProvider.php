<?php
declare(strict_types = 1);

namespace Serendipity\Job\Event;

use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;

class EventProvider extends AbstractProvider
{
    public function bootApp() : void
    {
        $this->container()->set(EventDispatcherInterface::class, new EventDispatcher());
    }
}