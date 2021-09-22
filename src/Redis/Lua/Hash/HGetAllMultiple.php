<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis\Lua\Hash;

use Serendipity\Job\Redis\Lua\Script;

class HGetAllMultiple extends Script
{
    public function getScript(): string
    {
        return <<<'LUA'
    local values = {}; 
    for i,v in ipairs(KEYS) do 
        if(redis.call('type',v).ok == 'hash') then
            values[#values+1] = redis.call('hgetall',v);
        end
    end
    return values;
LUA;
    }

    public function format($data): array
    {
        $result = [];
        foreach ($data ?? [] as $item) {
            if (!empty($item) && is_array($item)) {
                $temp = [];
                foreach ($item as $i => $iValue) {
                    $temp[$iValue] = $item[++$i];
                }

                $result[] = $temp;
            }
        }

        return $result;
    }
}
