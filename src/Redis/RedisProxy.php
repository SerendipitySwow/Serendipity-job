<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis;

use JetBrains\PhpStorm\Pure;
use Serendipity\Job\Redis\Pool\PoolFactory;

/**
 * @mixin \Redis
 */
class RedisProxy extends Redis
{
    protected string $poolName;

    #[Pure]
    public function __construct(PoolFactory $factory, string $pool)
    {
        parent::__construct($factory);

        $this->poolName = $pool;
    }
}
