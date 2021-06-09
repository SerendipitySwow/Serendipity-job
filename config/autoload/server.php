<?php
declare( strict_types = 1 );

use Swow\Http\Server;
use function Serendipity\Job\Kernel\serendipity_env;

return [
    'server' => Server::class,
    'host' => serendipity_env('SERVER_HOST', '127.0.0.1'),
    'type' => serendipity_env('SERVER_TYPE', \Swow\Socket::TYPE_TCP),
    'port' => (int) serendipity_env('SERVER_PORT', 9502),
    'backlog' => (int) serendipity_env('SERVER_BACKLOG', 8192),
    'multi' => (bool) serendipity_env('SERVER_MULTI', true),
];
