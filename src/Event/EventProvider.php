<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Event;

use Hyperf\Contract\ConfigInterface;
use SwowCloud\Job\Contract\EventDispatcherInterface;
use SwowCloud\Job\Kernel\Provider\AbstractProvider;
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
