<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

use function Serendipity\Job\Kernel\serendipity_env;

return [
    'default' => [
        'host' => serendipity_env('REDIS_HOST', '127.0.0.1'),
        'port' => serendipity_env('REDIS_PORT', 6379),
    ],
];
