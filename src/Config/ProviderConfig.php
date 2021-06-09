<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Config;

use Serendipity\Job\Config\Loader\YamlLoader;
use Serendipity\Job\Util\ApplicationContext;
use function class_exists;
use function is_string;
use function method_exists;

/**
 * Provider config allow the components set the configs to application.
 */
class ProviderConfig
{
    /**
     * @var array
     */
    private static array $providerConfigs = [];

    public static string $bootApp = 'BootApp';

    public static string $bootShutdown = 'BootShutdown';

    /**
     * Load and merge all provider configs from components.
     * Notice that this method will cached the config result into a static property,
     * call ProviderConfig::clear() method if you want to reset the static property.
     */
    public static function load (): array
    {
        if (!static::$providerConfigs) {
            $loader = ApplicationContext::getApplication()
                                        ->getContainer()
                                        ->get(YamlLoader::class);
            static::$providerConfigs = $loader->load(SERENDIPITY_JOB_PATH . '/config/providers.yaml');
        }
        return static::$providerConfigs;
    }

    public static function clear (): void
    {
        static::$providerConfigs = [];
    }

    public static function loadProviders (array $providers, string $method): void
    {
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider) && method_exists($provider, $method)) {
                call_user_func([ new $provider(), $method ]);
            }
        }
    }

}
