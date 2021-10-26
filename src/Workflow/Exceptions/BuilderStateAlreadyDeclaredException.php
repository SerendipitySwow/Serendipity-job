<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Workflow\Exceptions;

use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Workflow\Interfaces\StateInterface;

class BuilderStateAlreadyDeclaredException extends InvalidArgumentException
{
    #[Pure]
 public function __construct(StateInterface $state)
 {
     parent::__construct("Cannot add state \"{$state}\", it has already been declared.");
 }
}
