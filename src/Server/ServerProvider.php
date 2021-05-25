<?php
declare(strict_types = 1);

namespace Serendipity\Job\Server;

use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Serendipity\Job\Logger\LoggerFactory;
use Swow\Buffer;
use Swow\Coroutine;
use Swow\Socket;
use Swow\Socket\Exception;

class ServerProvider extends AbstractProvider
{
    protected StdoutLoggerInterface $stdoutLogger;

    protected LoggerInterface $logger;

    public function bootApp() : void
    {
        /**
         * @var Socket $server
         */
        $server             = $this->container()->make(ServerFactory::class)->start();
        $this->stdoutLogger = $this->container()->get(StdoutLoggerInterface::class);
        $this->logger       = $this->container()->get(LoggerFactory::class)->get();
        $this->stdoutLogger->debug('Serendipity-Job Start Successfully#');
        /*
         * 测试日志
        $this->logger->debug('Serendipity-Job Start Successfully#');
        */
        while (true) {
            /*
             * 每个$client都不一样,参考如下:
             */
            $client = $server->accept();
            /*
            dump($client);
 Swow\Socket {#170
  type: "TCP4"
  fd: 18
  timeout: array:5 [
    "dns" => -1
    "accept" => -1
    "connect" => -1
    "read" => -1
    "write" => -1
  ]
  established: true
  side: "none"
  sockname: array:2 [
    "address" => "127.0.0.1"
    "port" => 9502
  ]
  peername: array:2 [
    "address" => "127.0.0.1"
    "port" => 53315
  ]
  io_state: "idle"
}
            */
            Coroutine::run(function () use ($client)
            {
                $buffer = new Buffer();
                try {
                    while (true) {
                        $length  = $client->recv($buffer);
                        $content = $buffer->getContents();
                        $this->stdoutLogger->debug(sprintf('Buffer Content: %s', $content) . PHP_EOL);
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
