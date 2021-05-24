<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Provider;

class KernelProvider extends AbstractProvider
{
    protected static array $providers = [];

    public static function init(array $providers = []) : void
    {
        static::$providers = $providers;
    }

    public function bootApp() : void
    {

    }

    public function bootRequest() : void
    {
    }

    public function shutdown() : void
    {

    }
}
