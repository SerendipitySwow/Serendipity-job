<?php
declare(strict_types = 1);

namespace Serendipity\Job\Kernel;

use Closure;
use RuntimeException;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Util\ApplicationContext;
use Serendipity\Job\Util\Arr;
use Serendipity\Job\Util\Collection;

#-------------------------注意:所有的方法名称均以serendipity_开头避免和其他框架命名冲突 ----------------------------#
if (!function_exists('serendipity_value')) {
    /**
     * Return the default value of the given value for serendipity.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function serendipity_value(mixed $value) : mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param object|string $class
     *
     * @return string
     */
    function class_basename(object|string $class) : string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}
if (!function_exists('serendipity_call')) {
    /**
     * Call a callback with the arguments.
     *
     * @param mixed $callback
     * @param array $args
     *
     * @return mixed
     */
    function serendipity_call(mixed $callback, array $args = []) : mixed
    {
        if ($callback instanceof Closure) {
            $result = $callback(...$args);
        } elseif (is_object($callback) || (is_string($callback) && function_exists($callback))) {
            $result = $callback(...$args);
        } elseif (is_array($callback)) {
            [$object, $method] = $callback;
            $result = is_object($object) ? $object->{$method}(...$args) : $object::$method(...$args);
        } else {
            $result = call_user_func_array($callback, $args);
        }
        return $result;
    }
}
if (!function_exists('serendipity_env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string     $key
     * @param null|mixed $default
     *
     * @return array|bool|string|void
     */
    function serendipity_env(string $key, mixed $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return serendipity_value($default);
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }
        return $value;
    }
}
if (!function_exists('serendipity_data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed                 $target
     * @param null|array|int|string $key
     * @param null|mixed            $default
     *
     * @return mixed
     */
    function serendipity_data_get(mixed $target, array|int|string|null $key, mixed $default = null) : mixed
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return serendipity_value($default);
                }
                $result = [];
                foreach ($target as $item) {
                    $result[] = serendipity_data_get($item, $key);
                }
                return in_array('*', $key, true) ? Arr::collapse($result) : $result;
            }
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return serendipity_value($default);
            }
        }
        return $target;
    }
}
if (!function_exists('serendipity_data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param mixed        $target
     * @param array|string $key
     * @param mixed        $value
     * @param bool         $overwrite
     *
     * @return mixed
     */
    function serendipity_data_set(mixed &$target, array|string $key, mixed $value, bool $overwrite = true) : mixed
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        if (($segment = array_shift($segments)) === '*') {
            if (!Arr::accessible($target)) {
                $target = [];
            }
            if ($segments) {
                foreach ($target as &$inner) {
                   serendipity_data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (!Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }
               serendipity_data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
               serendipity_data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                $target[$segment] = [];
               serendipity_data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }
        return $target;
    }
}

if (!function_exists('serendipity_collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param null|mixed $value
     *
     * @return \Serendipity\Job\Util\Collection
     * @throws \JsonException
     */
    function serendipity_collect(mixed $value = null) : Collection
    {
        return new Collection($value);
    }
}

if (!function_exists('serendipity_config')) {
    function serendipity_config(string $key, $default = null)
    {
        if (!ApplicationContext::hasApplication()) {
            throw new RuntimeException('The application context lacks the container.');
        }
        $container = ApplicationContext::getApplication()->getContainer();
        if (!$container->has(ConfigInterface::class)) {
            throw new RuntimeException('ConfigInterface is missing in container.');
        }
        return $container->get(ConfigInterface::class)->get($key, $default);
    }
}


if (!function_exists('serendipity_make')) {
    function serendipity_make(string $name, array $parameters = [])
    {
        if (ApplicationContext::hasApplication()) {
            $container = ApplicationContext::getApplication()->getContainer();
            if (method_exists($container, 'make')) {
                return $container->make($name, $parameters);
            }
        }
        $parameters = array_values($parameters);
        return new $name(...$parameters);
    }
}

if (!function_exists('serendipity_tcp_pack')) {
    /**
     * @param string $data
     *
     * @return string
     */
    function serendipity_tcp_pack(string $data) : string
    {
        return pack('n', strlen($data)) . $data;
    }
}
if (!function_exists('serendipity_tcp_length')) {
    /**
     * @param string $head
     *
     * @return int
     */
    function serendipity_tcp_length(string $head) : int
    {
        return unpack('n', $head)[1];
    }
}
