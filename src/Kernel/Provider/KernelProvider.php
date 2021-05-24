<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Provider;

use Serendipity\Job\Config\ProviderConfig;
use Serendipity\Job\Kernel\Traits\Singleton;

class KernelProvider extends AbstractProvider
{
    use Singleton;

    protected static array $providers = [];

    public function bootApp() : void
    {
        static::$providers = ProviderConfig::load();
        ProviderConfig::loadProviders(static::$providers[ProviderConfig::$bootApp], ProviderConfig::$bootApp);
    }

    public function bootRequest() : void
    {
    }

    public function shutdown() : void
    {
        ProviderConfig::loadProviders(static::$providers[ProviderConfig::$bootShutdown], ProviderConfig::$bootShutdown);
    }

}
