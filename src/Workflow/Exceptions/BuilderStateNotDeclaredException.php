<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Exceptions;

use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Workflow\Interfaces\StateInterface;

class BuilderStateNotDeclaredException extends InvalidArgumentException
{
    #[Pure]
 public function __construct(StateInterface $state)
 {
     parent::__construct("Cannot use state \"{$state}\", since it has not been declared yet.");
 }
}
