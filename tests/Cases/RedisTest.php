<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SerendipityTest\Cases;

use PHPUnit\Framework\TestCase;
use Serendipity\Job\Kernel\Lock\RedisLock;

/**
 * @internal
 * @coversNothing
 */
class RedisTest extends TestCase
{
    public function testRedis(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $lock = make(RedisLock::class);
            $true = $lock->lock('sdfsdf', 5 * 60);
            $this->assertTrue($true, '加锁失败');
        }
    }
}
