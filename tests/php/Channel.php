<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

use SwowCloud\Archer\Archer;

require_once(dirname(__DIR__)) . '/../vendor/autoload.php';
// Swow\Debug\Debugger::runOnTTY();
$callback = function (string $method, ...$param) {
    return $param;
};
$task1 = Archer::taskDefer($callback, ['get', 'some_key']);
$task2 = Archer::taskDefer($callback, ['hget', 'a', 'b']);
$task3 = Archer::taskDefer($callback, ['lget', 'k1', 10]);

sleep(20);

var_dump($task1->recv(1));
var_dump($task2->recv(1));
var_dump($task3->recv(1));

exit;
$channel = new \Hyperf\Engine\Channel(10);

try {
//    $channel->push(2);
    sleep(1);
} catch (Throwable $throwable) {
    var_dump($throwable);
}

\Swow\Coroutine::run(function () use ($channel) {
    $channel->pop(1);
});
// var_dump($channel->__debugInfo());
exit;
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

// \Swow\Coroutine::run(function () use ($channel) {
//    sleep(3);
//    $channel->push(3);
// });
