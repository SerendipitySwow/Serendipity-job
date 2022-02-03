<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Subscriber;

use JetBrains\PhpStorm\ArrayShape;
use SwowCloud\Job\Db\DB;
use SwowCloud\Job\Event\UpdateWorkflowEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UpdateWorkflowSubscriber implements EventSubscriberInterface
{
    /**
     * @return string[]
     */
    #[ArrayShape([UpdateWorkflowEvent::UPDATE_WORKFLOW => 'string'])]
    public static function getSubscribedEvents(): array
    {
        return [
            UpdateWorkflowEvent::UPDATE_WORKFLOW => 'onUpdateWorkflow',
        ];
    }

    public function onUpdateWorkflow(UpdateWorkflowEvent $event): void
    {
        DB::execute(sprintf('update workflow set  status = %s where id = %s;', $event->status, $event->id));
    }
}
