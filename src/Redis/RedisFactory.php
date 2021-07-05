<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis;

use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Redis\Exception\InvalidRedisProxyException;

class RedisFactory
{
    /**
     * @var RedisProxy[]
     */
    protected array $proxies;

    public function __construct(ConfigInterface $config)
    {
        $redisConfig = $config->get('redis');

        foreach ($redisConfig as $poolName => $item) {
            $this->proxies[$poolName] = make(
                RedisProxy::class,
                ['pool' => $poolName]
            );
        }
    }

    public function get(string $poolName): RedisProxy
    {
        $proxy = $this->proxies[$poolName] ?? null;
        if (!$proxy instanceof RedisProxy) {
            throw new InvalidRedisProxyException('Invalid Redis proxy.');
        }

        return $proxy;
    }
}
