<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Provider;

use Hyperf\Di\Container;
use Hyperf\Utils\ApplicationContext;
use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Config\ProviderConfig;

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
    public function container(): ContainerInterface|Container
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

    private function initApplication(): void
    {
        static::$providers = ProviderConfig::load();
    }
}
