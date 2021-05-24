<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel\Provider;

use ReflectionClass;
use Serendipity\Job\Util\ApplicationContext;

class KernelProvider extends AbstractProvider
{
    protected static array $providers = [];

    public static function init(array $providers = []) : void
    {
        static::$providers = $providers;
    }

    public static function bootApp() : void
    {
        /**
         * @var \DI\Container $container
         */
        $container = ApplicationContext::getContainer();
        foreach (self::$providers as $interface => $provider) {
            if (class_exists($provider) && method_exists($provider, '__invoke')) {
                $container->set((string)$interface, new ReflectionClass($provider));
            }
        }
    }

    public static function bootRequest() : void
    {
    }

    public static function shutdown() : void
    {

    }
}
