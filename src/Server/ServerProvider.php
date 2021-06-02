<?php
declare(strict_types = 1);

namespace Serendipity\Job\Server;

use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Serendipity\Job\Logger\LoggerFactory;
use Swow\Coroutine;
use Swow\Http\Buffer;
use Swow\Http\Server\Response;
use Swow\Http\Status;
use Swow\Socket\Exception;
use const Swow\Errno\EMFILE;
use const Swow\Errno\ENFILE;
use const Swow\Errno\ENOMEM;

class ServerProvider extends AbstractProvider
{
    protected StdoutLoggerInterface $stdoutLogger;

    protected LoggerInterface $logger;

    public function bootApp() : void
    {
        /**
         * @var \Swow\Http\Server $server
         */
        $server             = $this->container()->make(ServerFactory::class)->start();
        $this->stdoutLogger = $this->container()->get(StdoutLoggerInterface::class);
        $this->logger       = $this->container()->get(LoggerFactory::class)->get();
        $this->stdoutLogger->debug('Serendipity-Job Start Successfully#');
        while (true) {
            try {
                $session = $server->acceptSession();
                Coroutine::run(function () use ($session)
                {
                    $buffer = new Buffer();
                    try {
                        while (true) {
                            if (!$session->isEstablished()) {
                                break;
                            }
                            $request = null;
                            try {
                                $request = $session->recvHttpRequest();
                                switch ($request->getPath()) {
                                    case '/':
                                    {
                                        $buffer->write(file_get_contents(SERENDIPITY_JOB_PATH . '/storage/task.php'));
                                        $response = new Response();
                                        $response->setStatus(Status::OK);
                                        $response->setHeader('Server', 'Serendipity-Job');
                                        $response->setBody($buffer);
                                        $session->sendHttpResponse($response);
                                        ## clear buffer
                                        $buffer->clear();
                                        $this->stdoutLogger->debug(sprintf('Http Client Fd[%s] Debug#', (string)$session->getFd()));
                                        break;
                                    }
                                    case '/greeter':
                                    {
                                        $session->respond('Hello Swow');
                                        break;
                                    }
                                    case '/echo':
                                    {
                                        $session->respond($request->getBodyAsString());
                                        break;
                                    }
                                    ## websokcet
                                    /*
                                    case '/chat':
                                    {
                                        if ($upgrade = $request->getUpgrade()) {
                                            if ($upgrade === $request::UPGRADE_WEBSOCKET) {
                                                $session->upgradeToWebSocket($request);
                                                $request = null;
                                                while (true) {
                                                    $frame = $session->recvWebSocketFrame();
                                                    $opcode = $frame->getOpcode();
                                                    switch ($opcode) {
                                                        case WebSocketOpcode::PING:
                                                            $session->sendString(WebSocketFrame::PONG);
                                                            break;
                                                        case WebSocketOpcode::PONG:
                                                            break;
                                                        case WebSocketOpcode::CLOSE:
                                                            break 2;
                                                        default:
                                                            $frame->getPayloadData()->rewind()->write("You said: {$frame->getPayloadData()}");
                                                            $session->sendWebSocketFrame($frame);
                                                    }
                                                }
                                                break;
                                            }
                                            throw new HttpException(HttpStatus::BAD_REQUEST, 'Unsupported Upgrade Type');
                                        }
                                        $session->respond(file_get_contents(__DIR__ . '/chat.html'));
                                        break;
                                    }
                                    */
                                    default:
                                    {
                                        $session->error(Status::NOT_FOUND);
                                    }
                                }
                            } catch (HttpException $exception) {
                                $session->error($exception->getCode(), $exception->getMessage());
                            }
                            if (!$request || !$request->getKeepAlive()) {
                                break;
                            }
                        }
                    } catch (Exception $exception) {
                        // you can log error here
                    }
                    finally {
                        $session->close();
                    }
                });
            } catch (SocketException | CoroutineException $exception) {
                if (in_array($exception->getCode(), [EMFILE, ENFILE, ENOMEM], true)) {
                    sleep(1);
                } else {
                    break;
                }
            }
        }
    }
}
