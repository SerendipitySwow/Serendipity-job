<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Lock;

use Exception;
use Hyperf\Utils\ApplicationContext;
use Serendipity\Job\Redis\RedisFactory;
use Swow\Coroutine;

class RedisLock
{
    /**
     * redis key前缀
     */
    private const REDIS_LOCK_KEY_PREFIX = 'redis:lock:';

    private array $lockedNames = [];

    private string $name;

    public function __construct(string $name = 'default')
    {
        $this->name = $name;
    }

    /**
     * 上锁
     *
     * @param string $name 锁名字
     * @param int $expire 锁有效期
     * @param int $retryTimes 重试次数
     * @param int $usleep 重试休息微秒
     */
    public function lock(string $name, int $expire = 5, int $retryTimes = 5, int $usleep = 10000): bool
    {
        $lock = false;
        $retryTimes = max($retryTimes, 1);
        $key = self::REDIS_LOCK_KEY_PREFIX . $name;
        while ($retryTimes-- > 0) {
            $kVal = microtime(true) + $expire + Coroutine::getCurrent()->getId();
            $lock = (bool) $this->getLock($key, $expire, $kVal); //上锁
            if ($lock) {
                $this->lockedNames[$key] = $kVal;
                break;
            }
            usleep($usleep);
        }

        return $lock;
    }

    /**
     * 解锁
     *
     * @return mixed
     */
    public function unlock(string $name): bool
    {
        $script = <<<'LUA'
            local key = KEYS[1]
            local value = ARGV[1]

            if (redis.call('exists', key) == 1 and redis.call('get', key) == value) 
            then
                return redis.call('del', key)
            end

            return 0
LUA;
        $key = self::REDIS_LOCK_KEY_PREFIX . $name;
        if (isset($this->lockedNames[$key])) {
            $val = $this->lockedNames[$key];

            return (bool) $this->execLuaScript($script, [$key, $val]);
        }

        return false;
    }

    /**
     * 获取锁并执行.
     *
     * @throws Exception
     */
    public function run(callable $func, string $name, int $expire = 5, int $retryTimes = 10, int $sleep = 10000): bool
    {
        if ($this->lock($name, $expire, $retryTimes, $sleep)) {
            try {
                $func();
            } finally {
                $this->unlock($name);
            }

            return true;
        }

        return false;
    }

    private function getLock($key, $expire, $value): mixed
    {
        $script = <<<'LUA'
            local key = KEYS[1]
            local value = ARGV[1]
            local ttl = ARGV[2]

            if (redis.call('setnx', key, value) == 1) then
                return redis.call('expire', key, ttl)
            elseif (redis.call('ttl', key) == -1) then
                return redis.call('expire', key, ttl)
            end
            
            return 0
LUA;

        return $this->execLuaScript($script, [$key, $value, $expire]);
    }

    /**
     * 执行lua脚本.
     */
    private function execLuaScript(string $script, array $params): mixed
    {
        $redis = ApplicationContext::getContainer()->get(RedisFactory::class)
            ->get($this->name);
        $hash = $redis->script('load', $script);

        return $redis->evalSha($hash, $params, 1);
    }
}
