<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Exceptions;

use JetBrains\PhpStorm\Pure;
use SwowCloud\Job\Workflow\Interfaces\TransitionInterface;

class BuilderTransitionAlreadyDeclaredException extends InvalidArgumentException
{
    #[Pure]
 public function __construct(TransitionInterface $transition)
 {
     parent::__construct("Cannot add transition \"{$transition}\", it has already been declared.");
 }
}
