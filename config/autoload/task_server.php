<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

return [
    // or DAG_SERVER_HOST DAG_SERVER_PORT
    'host' => env('TASK_SERVER_HOST', '127.0.0.1'),
    'port' => (int) env('TASK_SERVER_PORT', 9764),
];
