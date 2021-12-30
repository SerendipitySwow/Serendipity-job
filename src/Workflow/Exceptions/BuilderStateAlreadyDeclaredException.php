<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Workflow\Exceptions;

use JetBrains\PhpStorm\Pure;
use SwowCloud\Job\Workflow\Interfaces\StateInterface;

class BuilderStateAlreadyDeclaredException extends InvalidArgumentException
{
    #[Pure]
 public function __construct(StateInterface $state)
 {
     parent::__construct("Cannot add state \"{$state}\", it has already been declared.");
 }
}
