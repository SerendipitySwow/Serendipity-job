<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Provider;

use SwowCloud\Job\Config\ProviderConfig;
use SwowCloud\Job\Kernel\Traits\Singleton;

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
