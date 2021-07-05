<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Provider;

use Serendipity\Job\Config\ProviderConfig;
use Serendipity\Job\Kernel\Traits\Singleton;

class KernelProvider extends AbstractProvider
{
    use Singleton;

    public function bootApp(): void
    {
        ProviderConfig::loadProviders(
            static::$providers[$this->module][ProviderConfig::$bootApp],
            ProviderConfig::$bootApp
        );
    }

    public function bootRequest(): void
    {
    }

    public function shutdown(): void
    {
        ProviderConfig::loadProviders(
            static::$providers[$this->module][ProviderConfig::$bootShutdown],
            ProviderConfig::$bootShutdown
        );
    }
}
