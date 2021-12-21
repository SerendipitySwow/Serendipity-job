<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation;

use Psr\EventDispatcher\EventDispatcherInterface;
use SwowCloud\Job\Workflow\Exceptions\TransitionNotDeclaredException;
use SwowCloud\Job\Workflow\Implementation\Events\StateChanged;
use SwowCloud\Job\Workflow\Implementation\Events\StateChanging;
use SwowCloud\Job\Workflow\Interfaces\EngineInterface;
use SwowCloud\Job\Workflow\Interfaces\StateAwareInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionRepositoryInterface;

abstract class AbstractEngine implements EngineInterface
{
    private TransitionRepositoryInterface $repository;

    private ?EventDispatcherInterface $dispatcher;

    public function __construct(TransitionRepositoryInterface $repository, ?EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    public function execute(StateAwareInterface $item, TransitionInterface $transition): void
    {
        if (!($matched = $this->repository->find($transition))) {
            throw new TransitionNotDeclaredException($transition);
        }

        $this->dispatcher && $this->dispatcher->dispatch(new StateChanging($item, $matched->getNewState()));

        $item->setState($matched->getNewState());

        $this->dispatcher && $this->dispatcher->dispatch(new StateChanged($item, $matched->getOldState()));
    }
}
