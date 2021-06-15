<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Provider;

use Hyperf\Di\Container;
use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Config\ProviderConfig;
use Serendipity\Job\Util\ApplicationContext;

abstract class AbstractProvider implements ProviderContract
{
    protected ?string $module = null;

    protected static array $providers = [];

    public function __construct(string $module = null)
    {
        $this->module = $module;
        $this->initApplication();
    }

    #[Pure]
    public function container(): ContainerInterface | Container
    {
        return ApplicationContext::getContainer();
    }

    public function bootApp(): void
    {
        echo __METHOD__;
    }

    public function bootRequest(): void
    {
        echo __METHOD__;
    }

    public function shutdown(): void
    {
        echo __METHOD__;
    }

    protected function initApplication(): void
    {
        static::$providers = ProviderConfig::load();
    }
}
