<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

use Swow\Coroutine;
use SwowCloud\RedisLock\RedisLock;

require_once './bootstrap.php';
/* 测试redis-lock watchdog最大执行时间 不用给redis 一直续锁*/
//while (true) {
//    Coroutine::run(function ()  {
//        $lock = make(RedisLock::class);
//        $true = $lock->lock('sdfsdf', 5);
//    });
//    sleep(10);
//}

## \SwowCloud\Job\Kernel\Swow\Debugger::runOnTTY('debug');
## vars 可以查看所有变量
## p $var 可以随时查看$var的值
## p $var=1 可以修改$var的值

$i = 1;
$l = 1;
while (true) {
    sleep(3);
    ++$i;
    \Swow\Coroutine::run(function () use ($i) {
//        echo $i . PHP_EOL;
    });
    \Swow\Coroutine::run(function () use ($i, &$l) {
        $l = $i * $i;
//        echo $l . PHP_EOL;
    });
}
