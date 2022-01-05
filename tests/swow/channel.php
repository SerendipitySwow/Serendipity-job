<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

require_once '../bootstrap.php';

$waiter = new \SwowCloud\Job\Util\Waiter();
$result = $waiter->wait(function () {
    sleep(20);
}, 1);
dump($result);
//
//$channel = new \Swow\Channel();
//\Swow\Coroutine::run(function ()use($channel){
//   sleep(20);
//   $channel->push('呵呵');
//});
//$channel->pop(1000);
//var_dump($channel);
