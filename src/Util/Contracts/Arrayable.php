<?php

declare(strict_types = 1);

namespace Serendipity\Job\Util\Contracts;

interface Arrayable
{
    public function toArray() : array;
}
