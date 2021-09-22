<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

return [
    //or DAG_SERVER_HOST DAG_SERVER_PORT
    'host' => env('TASK_SERVER_HOST', '127.0.0.1'),
    'port' => (int) env('TASK_SERVER_PORT', 9764),
];
