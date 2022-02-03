<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
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

for ($c = C; $c--;) {
    Coroutine::run(function () use ($wr, $packer, $serializer) {
        $fp = stream_socket_client('tcp://127.0.0.1:9502', $errno, $errstr, 1);
        if (!$fp) {
            echo "{$errstr} ({$errno})\n";
        } else {
            stream_set_timeout($fp, 5);
            $i = 0;
            while (true) {
                ++$i;
                $packet = new \Multiplex\Packet($i, 'Hello Swow Server #' . random_int(10000, 99999));
                fwrite($fp, tcp_pack($packer->pack($packet)));
                dump($packet);
                echo PHP_EOL;
            }
        }
    });
}
