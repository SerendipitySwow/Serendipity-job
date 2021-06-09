<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Util\Contracts;

interface Jsonable
{
    public function __toString (): string;
}
