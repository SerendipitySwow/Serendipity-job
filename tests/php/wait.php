<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

use Swow\Signal;

require_once '../src/Kernel/Lock/RedisLock.php';
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$lock = new \SwowCloud\Job\Kernel\Lock\RedisLock($redis);

$true = $lock->lock('sdfsdf');
if ($true) {
    echo 111;
}
$pid = getmypid();
$count = 3;

echo "Press Ctrl + C\n";

do {
    Signal::wait(Signal::INT);
    var_dump(\Swow\Coroutine::getCurrent()
        ->getId());
    echo "\n"; // for ^C
} while ($count-- && print_r("Repeat {$count} times if you want to quit\n"));

echo "Quit\n";
