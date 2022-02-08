<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

use Swow\Coroutine;
use Swow\Socket;
use Swow\Sync\WaitReference;

$server = new Socket(Socket::TYPE_TCP);
Coroutine::run(function () use ($server) {
    $server->bind('127.0.0.1')->listen();
    try {
        while (true) {
            $client = $server->accept();
            Coroutine::run(function () use ($client) {
                try {
                    while (true) {
                        $read = $client->readString(5);
                        var_dump($read);
                        $client->sendString('测试测试' . $read);
                    }
                } catch (Socket\Exception $exception) {
                    echo $exception->getMessage();
                }
            });
        }
    } catch (Socket\Exception $exception) {
        echo $exception->getMessage();
    }
});

$wr = new WaitReference();
$client = new Socket(Socket::TYPE_TCP);
$client->connect($server->getSockAddress(), $server->getSockPort());
Coroutine::run(function () use ($server, $client, $wr) {
    for ($n = 0; $n < 10; $n++) {
        $packet = $client->readString(10);
        var_dump($packet);
    }
    $client->close();
});
for ($n = 0; $n < 10; $n++) {
    ($client->sendString((string) random_int(10000, 99999)));
}
WaitReference::wait($wr);
$server->close();

echo 'Done' . PHP_EOL;
