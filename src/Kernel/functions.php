<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel;

#-------------------------注意:所有的方法名称均以serendipity_开头避免和其他框架命名冲突 ----------------------------#

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
        return \simdjson_decode($json, true, 512) ?? Json::decode($json);
    }
}
