<?php

declare(strict_types = 1);

namespace Serendipity\Job\Util\Contracts;

interface Xmlable
{
    public function __toString() : string;
}
