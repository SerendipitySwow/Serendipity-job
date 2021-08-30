<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis\Lua\Hash;

use Serendipity\Job\Redis\Lua\Script;

class HIncrByFloatIfExists extends Script
{
    public function getScript(): string
    {
        return <<<'LUA'
    if(redis.call('type', KEYS[1]).ok == 'hash') then
        return redis.call('HINCRBYFLOAT', KEYS[1], ARGV[1], ARGV[2]);
    end
    return "";
LUA;
    }

    /**
     * @param null|float $data
     *
     * @return null|float|int|string
     */
    public function format($data): float|int|string|null
    {
        if (is_numeric($data)) {
            return $data;
        }

        return null;
    }

    protected function getKeyNumber(array $arguments): int
    {
        return 1;
    }
}
