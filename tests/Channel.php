<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

$channel = new \Swow\Channel();
\Swow\Coroutine::run(function () use ($channel) {
    try {
        $clourse = $channel->pop();
        var_dump($clourse);
    } catch (\Throwable $exception) {
        var_dump($exception->getMessage());
    }
});
\Swow\Coroutine::run(function () use ($channel) {
    sleep(3);
    $channel->push(3);
});
