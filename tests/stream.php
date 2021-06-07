<?php
declare(strict_types = 1);

use Swow\Sync\WaitReference;
use Swow\Coroutine;
const C = 100;
const N = 100;
$wr = new WaitReference();
// php_stream tcp server & client with 12.8K requests in single process
function tcp_pack(string $data) : string
{
    return pack('n', strlen($data)) . $data;
}

function tcp_length(string $head) : int
{
    return unpack('n', $head)[1];
}

Coroutine::run(function () use ($wr)
{
    $ctx    = stream_context_create(['socket' => ['so_reuseaddr' => true, 'backlog' => C]]);
    $server = stream_socket_server(
        'tcp://0.0.0.0:9502',
        $errno,
        $errstr,
        STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        $ctx
    );
    if (!$server) {
        echo "{$errstr} ({$errno})\n";
    } else {
        $c = 0;
        while ($conn = stream_socket_accept($server)) {
            Coroutine::run(function () use ($wr, $server, $conn, &$c)
            {
                stream_set_timeout($conn, 5);
                for ($n = N; $n--;) {
                    $data = fread($conn, tcp_length(fread($conn, 2)));
                    var_dump($data);
                    fwrite($conn, tcp_pack("Hello Swow Client #{$n}"));
                }
            });
        }
    }
});
