<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

use SwowCloud\Job\Task\EchoCrontab;

//TODO 考虑是否取消定时任务
return [
    'enable' => env('ENABLE_CRONTAB', false),
    'crontab' => [
        // Callback类型定时任务（默认）Example
        (new SwowCloud\Job\Crontab\Crontab())->setName('Foo')->setRule('*/5 * * * *')->setCallback([EchoCrontab::class, 'execute'])->setMemo('这是一个示例的定时任务'),
    ],
];
