<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation;

use Psr\EventDispatcher\EventDispatcherInterface;
use SwowCloud\Job\Workflow\Exceptions\BuilderStateAlreadyDeclaredException;
use SwowCloud\Job\Workflow\Exceptions\BuilderStateNotDeclaredException;
use SwowCloud\Job\Workflow\Exceptions\BuilderTransitionAlreadyDeclaredException;
use SwowCloud\Job\Workflow\Implementation\Entities\State;
use SwowCloud\Job\Workflow\Implementation\Entities\Transition;
use SwowCloud\Job\Workflow\Implementation\Entities\TransitionWithData;
use SwowCloud\Job\Workflow\Implementation\Repositories\ArrayRepository;
use SwowCloud\Job\Workflow\Interfaces\EngineInterface;
use SwowCloud\Job\Workflow\Interfaces\StateAwareInterface;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionRepositoryInterface;

/**
 * @see https://github.com/uuf6429/state-engine-php
 */
class Builder
{
    /**
     * @var array<string, StateInterface>
     */
    protected array $states = [];

    /**
     * @var array<string, TransitionInterface>
     */
    protected array $transitions = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new static();
    }

    public static function makeStateMutator(callable $getter, callable $setter): StateAwareInterface
    {
        return new class($getter, $setter) implements StateAwareInterface {
            private $getter;

            private $setter;

            public function __construct(callable $getter, callable $setter)
            {
                $this->getter = $getter;
                $this->setter = $setter;
            }

            public function getState(): StateInterface
            {
                return ($this->getter)();
            }

            public function setState(StateInterface $newState): void
            {
                ($this->setter)($newState);
            }
        };
    }

    public function addState(StateInterface $state): self
    {
        $stateId = $state->getId();
        if (isset($this->states[$stateId])) {
            throw new BuilderStateAlreadyDeclaredException($state);
        }

        $this->states[$stateId] = $state;

        return $this;
    }

    public function defState(string $name, ?string $description = null): self
    {
        return $this->addState(new State($name, $description));
    }

    public function addTransition(TransitionInterface $transition): self
    {
        if (!isset($this->states[$transition->getOldState()->getId()])) {
            throw new BuilderStateNotDeclaredException($transition->getOldState());
        }

        if (!isset($this->states[$transition->getNewState()->getId()])) {
            throw new BuilderStateNotDeclaredException($transition->getNewState());
        }

        $transitionId = $transition->getId();
        if (isset($this->transitions[$transitionId])) {
            throw new BuilderTransitionAlreadyDeclaredException($transition);
        }

        $this->transitions[$transitionId] = $transition;

        return $this;
    }

    public function defTransition(string $oldStateName, string $newStateName, ?string $description = null): self
    {
        return $this->addTransition(
            new Transition(
                $this->states[$oldStateName] ?? new State($oldStateName),
                $this->states[$newStateName] ?? new State($newStateName),
                $description
            )
        );
    }

    public function defDataTransition(string $oldStateName, array $data, string $newStateName, ?string $description = null): self
    {
        return $this->addTransition(
            new TransitionWithData(
                $this->states[$oldStateName] ?? new State($oldStateName),
                $data,
                $this->states[$newStateName] ?? new State($newStateName),
                $description
            )
        );
    }

    /**
     * @return ArrayRepository
     */
    public function getRepository(): TransitionRepositoryInterface
    {
        return new ArrayRepository(array_values($this->transitions));
    }

    /**
     * @return StateEngine
     */
    public function getEngine(?EventDispatcherInterface $eventDispatcher = null): EngineInterface
    {
        return new StateEngine($this->getRepository(), $eventDispatcher);
    }

    /**
     * @return StateMachine
     */
    public function getMachine(?EventDispatcherInterface $eventDispatcher = null): EngineInterface
    {
        return new StateMachine($this->getRepository(), $eventDispatcher);
    }
}
