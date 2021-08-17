<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Multiplex\Packer;
use Multiplex\Serializer\StringSerializer;
use Swow\Coroutine;
use Swow\Sync\WaitReference;

require_once '../vendor/autoload.php';
const C = 100;
const N = 100;
$wr = new WaitReference();
$packer = new Packer();
$serializer = new StringSerializer();
// php_stream tcp server & client with 12.8K requests in single process
function tcp_pack(string $data): string
{
    return pack('n', strlen($data)) . $data;
}

function tcp_length(string $head): int
{
    return unpack('n', $head)[1];
}

Coroutine::run(function () use ($wr, $packer, $serializer) {
    $ctx = stream_context_create(['socket' => ['so_reuseaddr' => true, 'backlog' => C]]);
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
            Coroutine::run(function () use ($wr, $server, $conn, $packer, $serializer, &$c) {
                stream_set_timeout($conn, 5);
                while (true) {
                    $data = fread($conn, tcp_length(fread($conn, 2)));
                    $packet = $packer->unpack($data);
                    if (!$packet->getId()) {
                        echo '发生了错误';
                    } else {
                        echo 'success#';
                    }
                    echo PHP_EOL;
                }
            });
        }
    }
});
