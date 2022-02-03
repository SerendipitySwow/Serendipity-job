<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Exceptions;

use JetBrains\PhpStorm\Pure;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;

class BuilderStateNotDeclaredException extends InvalidArgumentException
{
    #[Pure]
 public function __construct(StateInterface $state)
 {
     parent::__construct("Cannot use state \"{$state}\", since it has not been declared yet.");
 }
}
