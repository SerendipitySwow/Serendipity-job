<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Exceptions;

use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Workflow\Interfaces\TransitionInterface;

class TransitionNotDeclaredException extends RuntimeException
{
    #[Pure]
 public function __construct(TransitionInterface $transition)
 {
     parent::__construct("Cannot apply transition \"{$transition}\"; no such transition was defined.");
 }
}
