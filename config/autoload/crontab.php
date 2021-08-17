<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Serendipity\Job\Task\EchoCrontab;

return [
    'enable' => env('ENABLE_CRONTAB', true),
    'crontab' => [
        // Callback类型定时任务（默认）Example
        (new Serendipity\Job\Crontab\Crontab())->setName('Foo')->setRule('*/5 * * * *')->setCallback([EchoCrontab::class, 'execute'])->setMemo('这是一个示例的定时任务'),
    ],
];
