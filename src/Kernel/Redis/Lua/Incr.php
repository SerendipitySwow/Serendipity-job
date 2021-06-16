<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Redis\Lua;

class Incr extends Script
{
    protected function getScript(): string
    {
        return <<<'LUA'
 local current = redis.call('incr',KEYS[1]);
                local t = redis.call('ttl',KEYS[1]);
                if t == -1 then
                redis.call('expire',KEYS[1],ARGV[1])
                end;
                return current;
LUA;
    }

    protected function getKeyNums(): int
    {
        return 1;
    }
}
