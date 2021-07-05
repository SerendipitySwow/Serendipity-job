<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Util;

use ArrayObject;
use Closure;
use Hyperf\Engine\Coroutine as Co;

class Context
{
    protected static array $nonCoContext = [];

    public static function set(string $id, $value)
    {
        if (Coroutine::inCoroutine()) {
            Co::getContextFor()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }

        return $value;
    }

    public static function get(string $id, $default = null, $coroutineId = null)
    {
        if (Coroutine::inCoroutine()) {
            return Co::getContextFor($coroutineId)[$id] ?? $default;
        }

        return static::$nonCoContext[$id] ?? $default;
    }

    public static function has(string $id, $coroutineId = null): bool
    {
        if (Coroutine::inCoroutine()) {
            return isset(Co::getContextFor($coroutineId)[$id]);
        }

        return isset(static::$nonCoContext[$id]);
    }

    /**
     * Release the context when you are not in coroutine environment.
     */
    public static function destroy(string $id): void
    {
        unset(static::$nonCoContext[$id]);
    }

    /**
     * Copy the context from a coroutine to current coroutine.
     */
    public static function copy(int $fromCoroutineId, array $keys = []): void
    {
        $from = Co::getContextFor($fromCoroutineId);
        if ($from === null) {
            return;
        }

        $current = Co::getContextFor();
        $current?->exchangeArray($keys ? Arr::only($from->getArrayCopy(), $keys) : $from->getArrayCopy());
    }

    /**
     * Retrieve the value and override it by closure.
     */
    public static function override(string $id, Closure $closure)
    {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);

        return $value;
    }

    /**
     * Retrieve the value and store it if not exists.
     */
    public static function getOrSet(string $id, mixed $value): mixed
    {
        if (!self::has($id)) {
            return self::set($id, value($value));
        }

        return self::get($id);
    }

    public static function getContainer(): ArrayObject | array | null
    {
        if (Coroutine::inCoroutine()) {
            return Co::getContextFor();
        }

        return static::$nonCoContext;
    }
}
