<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$socket = new \Swow\Socket(\Swow\Socket::TYPE_TCP);
$socket->connect('127.0.0.1', 9764);
for ($i = 0; $i < 10; $i++) {
    $socket->sendString($i . "\r\n");
    sleep(5);
    dump($socket->recvString());
}
