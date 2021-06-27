<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Config;

use Hyperf\Utils\ApplicationContext;
use Serendipity\Job\Config\Loader\YamlLoader;
use function class_exists;
use function is_string;
use function method_exists;

/**
 * Provider config allow the components set the configs to application.
 */
class ProviderConfig
{
    private static array $providerConfigs = [];

    public static string $bootApp = 'BootApp';

    public static string $bootShutdown = 'BootShutdown';

    /**
     * Load and merge all provider configs from components.
     * Notice that this method will cached the config result into a static property,
     * call ProviderConfig::clear() method if you want to reset the static property.
     */
    public static function load(): array
    {
        if (!static::$providerConfigs) {
            $loader = ApplicationContext::getContainer()
                ->get(YamlLoader::class);
            static::$providerConfigs = $loader->load(BASE_PATH . '/config/providers.yaml');
        }

        return static::$providerConfigs;
    }

    public static function clear(): void
    {
        static::$providerConfigs = [];
    }

    public static function loadProviders(array $providers, string $method): void
    {
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider) && method_exists($provider, $method)) {
                call_user_func([new $provider(), $method]);
            }
        }
    }
}
