<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Swow\Coroutine;

$coroutine = Coroutine::run(function () {
    try {
        \Swow\defer(function () {
            echo Coroutine::getCurrent()
                ->getId() . '已退出.' . PHP_EOL;
        });
        echo 'defer---------------------' . PHP_EOL;

        var_dump(file_get_contents('http://www.baidu.com'));
    } catch (Throwable $e) {
    }
});
Coroutine::run(static function () use ($coroutine) {
    var_dump($coroutine->getState());
    var_dump($coroutine->throw(new Exception('111')));
});
