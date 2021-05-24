<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Provider;

abstract class AbstractProvider implements ProviderContract
{

    public static function bootApp() : void
    {
        echo __METHOD__;
    }

    public static function bootRequest() : void
    {
        echo __METHOD__;
    }

    public static function shutdown() : void
    {
        echo __METHOD__;
    }
}
