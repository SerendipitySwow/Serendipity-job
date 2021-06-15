<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Config;

use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;

class ConfigProvider extends AbstractProvider
{
    protected static string $interface = ConfigInterface::class;

    public function bootApp(): void
    {
        $this->container()
            ->make(ConfigFactory::class);
    }
}
