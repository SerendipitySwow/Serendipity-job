<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

return [
    'default' => [
        'host' => '127.0.0.1',
        'port' => 4150,
        'connect_timeout' => 10,
        'wait_timeout' => 3,
        'heartbeat' => -1,
        // 因为 Nsq 服务默认的闲置时间是 60s，故框架维护的最大闲置时间应小于 60s
        'max_idle_time' => 30,
    ],
];
