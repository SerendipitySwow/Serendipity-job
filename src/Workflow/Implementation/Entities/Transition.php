<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Entities;

use SwowCloud\Job\Workflow\Interfaces\DescribableInterface;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;
use SwowCloud\Job\Workflow\Interfaces\TransitionInterface;

class Transition implements TransitionInterface, DescribableInterface
{
    private StateInterface $oldState;

    private StateInterface $newState;

    private ?string $description;

    public function __construct(StateInterface $oldState, StateInterface $newState, ?string $description = null)
    {
        $this->oldState = $oldState;
        $this->newState = $newState;
        $this->description = $description;
    }

    public function getOldState(): StateInterface
    {
        return $this->oldState;
    }

    public function getNewState(): StateInterface
    {
        return $this->newState;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function equals($other): bool
    {
        return $other instanceof TransitionInterface &&
            $this->getId() === $other->getId();
    }

    public function getId(): string
    {
        return "({$this->oldState->getId()}) -> ({$this->newState->getId()})";
    }

    public function __toString(): string
    {
        return "{$this->oldState->getId()} -> {$this->newState->getId()}";
    }
}
