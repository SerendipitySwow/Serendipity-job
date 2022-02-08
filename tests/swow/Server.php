<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

$server = new Swow\Socket(Swow\Socket::TYPE_TCP);
$server->bind('127.0.0.1', 9764)->listen();
while (true) {
    $client = $server->accept();
    Swow\Coroutine::run(function () use ($client) {
        echo "No.{$client->getFd()} established" . PHP_EOL;
        $buffer = new Swow\Buffer();
        try {
            while (true) {
                $length = $client->recv($buffer);
                if ($length === 0) {
                    break;
                }
                echo "No.{$client->getFd()} say: \"" . addcslashes($buffer->toString(), "\r\n") . '"' . PHP_EOL;
                for ($i = 0; $i < 20; $i++) {
                    $client->sendString("hello world\r\n");
                }
            }
            echo "No.{$client->getFd()} closed" . PHP_EOL;
        } catch (Swow\Socket\Exception $exception) {
            echo "No.{$client->getFd()} goaway! {$exception->getMessage()}" . PHP_EOL;
        } catch (Throwable $exception) {
            var_dump($exception);
        }
    });
}
