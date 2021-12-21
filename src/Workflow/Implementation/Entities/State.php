<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Implementation\Entities;

use SwowCloud\Job\Workflow\Interfaces\DescribableInterface;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;

class State implements StateInterface, DescribableInterface
{
    private string $name;

    private ?string $description;

    public function __construct(string $name, ?string $description = null)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function equals($other): bool
    {
        return $other instanceof StateInterface &&
            $this->getId() === $other->getId();
    }

    public function getId(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
