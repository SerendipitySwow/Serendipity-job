<?php
declare(strict_types = 1);

use function Serendipity\Job\Kernel\env;

return [
    'host'    => env('SERVER_HOST', '127.0.0.1'),
    'type'    => env('SERVER_TYPE', \Swow\Socket::TYPE_TCP),
    'port'    => (int)env('SERVER_PORT', 9502),
    'backlog' => (int)env('SERVER_BACKLOG', 8192),
    'multi'   => (bool)env('SERVER_MULTI', true),
];
