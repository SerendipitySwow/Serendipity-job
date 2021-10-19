<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Implementation;

use Psr\EventDispatcher\EventDispatcherInterface;
use Serendipity\Job\Workflow\Exceptions\TransitionNotDeclaredException;
use Serendipity\Job\Workflow\Interfaces\EngineInterface;
use Serendipity\Job\Workflow\Interfaces\StateAwareInterface;
use Serendipity\Job\Workflow\Interfaces\TransitionInterface;
use Serendipity\Job\Workflow\Interfaces\TransitionRepositoryInterface;

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

        $this->dispatcher && $this->dispatcher->dispatch(new Serendipity\Job\Workflow\Implementation\Events\StateChanging($item, $matched->getNewState()));

        $item->setState($matched->getNewState());

        $this->dispatcher && $this->dispatcher->dispatch(new Serendipity\Job\Workflow\Implementation\Events\StateChanged($item, $matched->getOldState()));
    }
}
