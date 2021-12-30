<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Config;

use Hyperf\Utils\ApplicationContext;
use SwowCloud\Job\Config\Loader\YamlLoader;
use function class_exists;
use function is_string;
use function method_exists;

/**
 * Provider config allow the components set the configs to application.
 */
class ProviderConfig
{
    /**
     * @var array<int,string>
     */
    private static array $providerConfigs = [];

    public static string $bootApp = 'BootApp';

    public static string $bootShutdown = 'BootShutdown';

    /**
     * Load and merge all provider configs from components.
     * Notice that this method will cache the config result into a static property,
     * call ProviderConfig::clear() method if you want to reset the static property.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @return array<string,string[]>
     */
    public static function load(): array
    {
        if (!self::$providerConfigs) {
            $loader = ApplicationContext::getContainer()
                ->get(YamlLoader::class);
            self::$providerConfigs = $loader->load(BASE_PATH . '/config/providers.yaml');
        }

        return self::$providerConfigs;
    }

    public static function clear(): void
    {
        self::$providerConfigs = [];
    }

    /**
     * @param array<string|\SwowCloud\Job\Kernel\Provider\ProviderContract> $providers
     */
    public static function loadProviders(array $providers, string $method): void
    {
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider) && method_exists($provider, $method)) {
                $provider = make($provider);
                $provider->{$method}();
            }
        }
    }
}
