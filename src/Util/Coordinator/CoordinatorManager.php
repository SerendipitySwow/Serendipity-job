<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util\Coordinator;

class CoordinatorManager
{
    /**
     * A container that is used for storing coordinator.
     */
    private static array $container = [];

    /**
     * You should initialize a Coordinator with the identifier before use it.
     */
    public static function initialize(string $identifier): void
    {
        static::$container[$identifier] = new Coordinator();
    }

    /**
     * Get a Coordinator from container by the identifier.
     *
     * @throws \RuntimeException when the Coordinator with the identifier has not initialization
     */
    public static function until(string $identifier): Coordinator
    {
        if (!isset(static::$container[$identifier])) {
            static::$container[$identifier] = new Coordinator();
        }

        return static::$container[$identifier];
    }

    /**
     * Remove the Coordinator by the identifier from container after used,
     * otherwise memory leaks will occur.
     */
    public static function clear(string $identifier): void
    {
        unset(static::$container[$identifier]);
    }
}
