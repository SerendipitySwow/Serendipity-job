<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Traits;

use SwowCloud\Job\Workflow\Interfaces\DescribableInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionRepositoryInterface;

/**
 * This trait provides a method for generating a Plant UML diagram of the various states and transitions, assuming the
 * current class is a {@see TransitionRepositoryInterface}.
 */
trait Plantable
{
    public function toPlantUML(): string
    {
        /* @var $this TransitionRepositoryInterface */
        return implode(PHP_EOL, array_merge(
            ['@startuml', ''],
            array_map(
                static function (TransitionInterface $transition): string {
                    $oldState = $transition->getOldState();
                    $newState = $transition->getNewState();
                    $oldText = $oldState instanceof DescribableInterface ? $oldState->getDescription() : $oldState->getName();
                    $newText = $newState instanceof DescribableInterface ? $newState->getDescription() : $newState->getName();

                    $result = "({$oldText}) --> ({$newText})";

                    if ($transition instanceof DescribableInterface) {
                        $result .= " : {$transition->getDescription()}";
                    }

                    return $result;
                },
                iterator_to_array($this)
            ),
            ['', '@enduml']
        ));
    }
}
