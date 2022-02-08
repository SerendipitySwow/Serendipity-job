<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Exceptions;

use JetBrains\PhpStorm\Pure;
use SwowCloud\Job\Workflow\Interfaces\TransitionInterface;

class TransitionNotDeclaredException extends RuntimeException
{
    #[Pure]
 public function __construct(TransitionInterface $transition)
 {
     parent::__construct("Cannot apply transition \"{$transition}\"; no such transition was defined.");
 }
}
