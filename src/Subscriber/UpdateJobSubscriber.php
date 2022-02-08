<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Subscriber;

use JetBrains\PhpStorm\ArrayShape;
use SwowCloud\Job\Db\DB;
use SwowCloud\Job\Event\UpdateJobEvent;
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
