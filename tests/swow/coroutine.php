<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

\Swow\Coroutine::run(function () {
    $coroutine = \Swow\Coroutine::getCurrent();
    \Swow\defer(function () use ($coroutine) {
        var_dump($coroutine->getTrace());
    });
    var_dump(2);
});
