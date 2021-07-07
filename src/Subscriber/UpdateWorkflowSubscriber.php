<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Subscriber;

use JetBrains\PhpStorm\ArrayShape;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Event\UpdateWorkflowEvent;
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
