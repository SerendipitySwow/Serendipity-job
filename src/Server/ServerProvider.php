<?php
declare(strict_types = 1);

namespace Serendipity\Job\Server;

use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Swow\Socket;
use Swow\Socket\Exception;

class ServerProvider extends AbstractProvider
{
    public function bootApp() : void
    {
        $server = $this->container()->make(ServerFactory::class)->start();
        dd($server);
        $server->bind('127.0.0.1', 9764)->listen();
        while (true) {
            $client = $server->accept();
            \Swow\Coroutine::run(function () use ($client)
            {
                $buffer = new \Swow\Buffer();
                try {
                    while (true) {
                        $length = $client->recv($buffer);
                        if ($length === 0) {
                            break;
                        }
                        $offset = 0;
                        while (true) {
                            $eof = strpos($buffer->toString(), "\r\n\r\n", $offset);
                            if ($eof === false) {
                                break;
                            }
                            $client->sendString(
                                "HTTP/1.1 200 OK\r\n" .
                                "Connection: keep-alive\r\n" .
                                "Content-Length: 0\r\n\r\n"
                            );
                            $requestLength = $eof + strlen("\r\n\r\n");
                            if ($requestLength === $length) {
                                $buffer->clear();
                                break;
                            }  /* < */
                            $offset += $requestLength;
                        }
                    }
                } catch (Exception $exception) {
                    echo "No.{$client->getFd()} goaway! {$exception->getMessage()}" . PHP_EOL;
                }
            });
        }
    }
}
