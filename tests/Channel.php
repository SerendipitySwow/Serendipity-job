<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
$channel = new \Swow\Channel();
$coroutine = \Swow\Coroutine::run(function () use ($channel) {
    try {
        \Swow\defer(function () {
            echo 1;
        });
        sleep(1);
        file_get_contents('https://www.baidu.com');
        $clourse = $channel->push(random_int(11111, 222222));
        var_dump($clourse);
    } catch (\Throwable $exception) {
        var_dump($exception->getMessage());
    }
});
sleep(1);
$coroutine->kill();
var_dump($coroutine->isAvailable());
try {
    var_dump($channel->pop(2000));
    sleep(60);
} catch (Throwable $Throwable) {
}

//\Swow\Coroutine::run(function () use ($channel) {
//    sleep(3);
//    $channel->push(3);
//});
