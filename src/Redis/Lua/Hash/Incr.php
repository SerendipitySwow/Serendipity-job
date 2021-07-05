<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis\Lua\Hash;

use Serendipity\Job\Redis\Lua\Script;

class Incr extends Script
{
    public function getScript(): string
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

    protected function getKeyNumber(array $arguments): int
    {
        return 1;
    }

    public function format($data): int | string | null
    {
        if (is_numeric($data)) {
            return $data;
        }

        return null;
    }
}
