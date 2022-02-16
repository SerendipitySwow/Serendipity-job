<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

require_once '../vendor/autoload.php';

\SwowCloud\Job\Kernel\Swow\Debugger::runOnTTY('debug');
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
    \Swow\Coroutine::run(function () use ($i,&$l) {
        $l = $i * $i;
//        echo $l . PHP_EOL;
    });
}
