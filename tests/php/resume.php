<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

$coroutine = new Swow\Coroutine(function () {
    echo "End\r\n";
    \Swow\Coroutine::getCurrent()->yield();
});
echo "Resume\r\n";
$coroutine->resume();
echo "Out\r\n";
$coroutine->resume();
echo 'Never here';
