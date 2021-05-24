<?php

declare(strict_types = 1);

namespace Serendipity\Job\Config;

use Serendipity\Job\Config\Loader\YamlLoader;
use Serendipity\Job\Util\ApplicationContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;
use function class_exists;
use function is_string;
use function method_exists;
use function Serendipity\Job\Kernel\make;

/**
 * Provider config allow the components set the configs to application.
 */
class ProviderConfig
{
    /**
     * @var array
     */
    private static array $providerConfigs = [];

    private static string $bootApp = 'BootApp';

    /**
     * Load and merge all provider configs from components.
     * Notice that this method will cached the config result into a static property,
     * call ProviderConfig::clear() method if you want to reset the static property.
     */
    public static function load() : array
    {
        if (!static::$providerConfigs) {
            $fileLocator = make(FileLocator::class, ['paths' => [SERENDIPITY_JOB_PATH . '/config/']]);
            $container   = ApplicationContext::getContainer();
            $container->set(FileLocatorInterface::class, $fileLocator);
            $loader                  = make(YamlLoader::class, []);
            $providers               = $loader->load(SERENDIPITY_JOB_PATH . '/config/providers.yaml');
            static::$providerConfigs = static::loadProviders($providers[static::$bootApp]);
        }
        return static::$providerConfigs;
    }

    public static function clear() : void
    {
        static::$providerConfigs = [];
    }

    protected static function loadProviders(array $providers) : array
    {
        $providerConfigs = [];
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider) && method_exists($provider, 'bootApp')) {
                $providerConfigs[] = call_user_func([new $provider(), 'bootApp']);
            }
        }

        return static::merge(...$providerConfigs);
    }

    protected static function merge(...$arrays) : array
    {
        if (empty($arrays)) {
            return [];
        }
        $result = array_merge_recursive(...$arrays);
        if (isset($result['dependencies'])) {
            $dependencies           = array_column($arrays, 'dependencies');
            $result['dependencies'] = array_merge(...$dependencies);
        }
        return $result;
    }
}
