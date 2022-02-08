<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

$coroutine = \Swow\Coroutine::run(function () {
    $coroutine = \Swow\Coroutine::getCurrent();
    \Swow\defer(function () use ($coroutine) {
        var_dump($coroutine->getTrace());
    });
//    var_dump(2);
    // 在协程内部抛出异常defer不会被执行
    $coroutine->throw(new Exception('11111'));
});
// just do not
$coroutine->throw(new Exception('11111'));

