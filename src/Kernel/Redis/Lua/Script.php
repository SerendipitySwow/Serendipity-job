<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Redis\Lua;

use Psr\Container\ContainerInterface;
use Redis;

abstract class Script
{
    protected ?Redis $redis = null;

    protected ?ContainerInterface $container = null;

    public function __construct(ContainerInterface $container, Redis $redis)
    {
        $this->container = $container;
        $this->redis = $redis;
    }

    abstract protected function getScript(): string;

    abstract protected function getKeyNums(): int;

    public function execLuaScript(array $params): mixed
    {
        $hash = $this->redis->script('load', $this->getScript());

        return $this->redis->evalSha($hash, $params, $this->getKeyNums());
    }
}
