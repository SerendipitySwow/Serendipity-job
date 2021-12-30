<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel;

#-------------------------注意:所有的方法名称均以serendipity_开头避免和其他框架命名冲突 ----------------------------#

use Hyperf\Utils\Arr;
use Hyperf\Utils\Codec\Json;
use Throwable;

if (!function_exists('serendipity_format_throwable')) {
    function serendipity_format_throwable(Throwable $throwable): string
    {
        return sprintf(
            "%s: %s(%s) in %s:%s\nStack trace:\n%s",
            get_class($throwable),
            $throwable->getMessage(),
            $throwable->getCode(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        );
    }
}

if (!function_exists('serendipity_json_decode')) {
    function serendipity_json_decode(string $json): array|object
    {
        /* @noinspection PhpUndefinedFunctionInspection */
        return \simdjson_decode($json, true, 512) ?? Json::decode($json);
    }
}

if (!function_exists('server_ip')) {
    function server_ip(): string
    {
        $ifaces = net_get_interfaces();

        return Arr::get($ifaces, 'en0.unicast.2.address', '127.0.0.1');
    }
}
