<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel;

#-------------------------注意:所有的方法名称均以serendipity_开头避免和其他框架命名冲突 ----------------------------#

use Throwable;

if (!function_exists('serendipity_tcp_pack')) {
    function serendipity_tcp_pack(string $data): string
    {
        return pack('n', strlen($data)) . $data;
    }
}
if (!function_exists('serendipity_tcp_length')) {
    function serendipity_tcp_length(string $head): int
    {
        return unpack('n', $head)[1];
    }
}
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
