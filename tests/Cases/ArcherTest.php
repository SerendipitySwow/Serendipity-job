<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SerendipityTest\Cases;

use PHPUnit\Framework\TestCase;
use SerendipitySwow\Archer\Archer;

/**
 * @internal
 * @coversNothing
 */
class ArcherTest extends TestCase
{
    public function testArcher()
    {
        $callback = function (string $method, ...$param) {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);

            return $redis->{$method}(...$param);
        };
        $task1 = Archer::taskDefer($callback, ['get', 'some_key']);
        $task2 = Archer::taskDefer($callback, ['hget', 'a', 'b']);
        var_dump($task1->recv());
        var_dump($task2->recv());
    }
}
