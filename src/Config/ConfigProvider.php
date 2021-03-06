<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Config;

use Hyperf\Contract\ConfigInterface;
use SwowCloud\Job\Kernel\Provider\AbstractProvider;

class ConfigProvider extends AbstractProvider
{
    protected static string $interface = ConfigInterface::class;

    /**
     * @throws \Hyperf\Di\Exception\NotFoundException
     */
    public function bootApp(): void
    {
        $this->container()
            ->make(ConfigFactory::class);
    }
}
