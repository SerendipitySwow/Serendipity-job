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

for ($c = C; $c--;) {
    Coroutine::run(function () use ($wr)
    {
        $fp = stream_socket_client('tcp://127.0.0.1:9502', $errno, $errstr, 1);
        if (!$fp) {
            echo "{$errstr} ({$errno})\n";
        } else {
            stream_set_timeout($fp, 5);
            while (true){
                fwrite($fp, tcp_pack('Hello Swow Server #' .random_int(10000,99999)));
                $length = tcp_length(fread($fp, 2));
                $data = fread($fp,$length);
                var_dump($data);
            }
        }
    });
}
