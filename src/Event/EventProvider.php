<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Event;

use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventProvider extends AbstractProvider
{
    public function bootApp(): void
    {
        $dispatcher = new EventDispatcher();
        $this->container()
            ->set(EventDispatcherInterface::class, $dispatcher);
        $config = $this->container()
            ->get(ConfigInterface::class);
        $subscribers = $config->get('subscribers');
        foreach ($subscribers as $subscriber) {
            $subscriber = $this->container()->get($subscriber);
            if ($subscriber instanceof EventSubscriberInterface) {
                $dispatcher->addSubscriber($subscriber);
            }
        }
    }
}
