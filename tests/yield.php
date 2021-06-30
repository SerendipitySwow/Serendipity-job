<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

$coroutine = \Swow\Coroutine::run(function () {
    echo 'rnm那个是第一行' . PHP_EOL;
    \Swow\Coroutine::getCurrent()->yield();
    sleep(10);
    echo '停留10s后输出' . PHP_EOL;
});
var_dump($coroutine);
echo "👴是第一行\n";
\Swow\Coroutine::run(function () use ($coroutine) {
    echo "Beginning\n";
    var_dump($coroutine);
    $coroutine->resume(); //这一步会去执行 \Swow\Coroutine::getCurrent()->yield(); 挂起协程
});
var_dump($coroutine);
$coroutine->resume(); //继续恢复协程
