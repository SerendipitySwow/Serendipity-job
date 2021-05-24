<?php
declare(strict_types = 1);

namespace Serendipity\Job\Server;

use Serendipity\Job\Kernel\Provider\AbstractProvider;

class ServerProvier extends AbstractProvider
{
    public static function Bootstrap() : void
    {
        echo 111111;
    }
}
