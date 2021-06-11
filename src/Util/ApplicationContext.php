<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util;

use Psr\Container\ContainerInterface;
use TypeError;

class ApplicationContext
{
    private static ?ContainerInterface $container;

    /**
     * @throws TypeError
     */
    public static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    public static function hasContainer(): bool
    {
        return isset(self::$container);
    }

    public static function setContainer(ContainerInterface $container): ContainerInterface
    {
        self::$container = $container;

        return $container;
    }
}
