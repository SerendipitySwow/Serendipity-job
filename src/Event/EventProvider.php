<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Event;

use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;

class EventProvider extends AbstractProvider
{
    public function bootApp(): void
    {
        $this->container()
            ->set(EventDispatcherInterface::class, new EventDispatcher());
    }
}
