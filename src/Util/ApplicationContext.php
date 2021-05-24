<?php

declare(strict_types = 1);

namespace Serendipity\Job\Util;

use Psr\Container\ContainerInterface;

class ApplicationContext
{
    /**
     * @var null|ContainerInterface
     */
    private static ?ContainerInterface $container = null;

    /**
     * @return \DI\Container
     */
    public static function getContainer() : ContainerInterface
    {
        return self::$container;
    }

    public static function hasContainer() : bool
    {
        return isset(self::$container);
    }

    public static function setContainer(ContainerInterface $container) : ContainerInterface
    {
        self::$container = $container;
        return $container;
    }
}
