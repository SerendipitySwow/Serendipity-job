<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\JobTest\Cases;

use PHPUnit\Framework\TestCase;
use Swow\Coroutine;
use SwowCloud\RedisLock\RedisLock;

/**
 * @internal
 * @coversNothing
 */
class RedisTest extends TestCase
{
    public function testRedis(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Coroutine::run(function () use ($i) {
                $lock = make(RedisLock::class);
                $true = $lock->lock('sdfsdf', 5);
                $this->assertTrue($true, '加锁成功');
            });
        }
    }
}
