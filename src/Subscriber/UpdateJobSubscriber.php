<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Subscriber;

use JetBrains\PhpStorm\ArrayShape;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Event\UpdateJobEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateJobSubscriber implements EventSubscriberInterface
{
    #[ArrayShape([UpdateJobEvent::UPDATE_JOB => 'string'])]
    public static function getSubscribedEvents(): array
    {
        return [
            UpdateJobEvent::UPDATE_JOB => 'onUpdateJob',
        ];
    }

    public function onUpdateJob(UpdateJobEvent $event): void
    {
        DB::execute(sprintf('update task set  status = %s where id = %s;', $event->status, $event->id));
    }
}
