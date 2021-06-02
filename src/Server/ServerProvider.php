<?php
declare(strict_types = 1);

namespace Serendipity\Job\Server;

use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Serendipity\Job\Logger\LoggerFactory;
use Serendipity\Job\Serializer\Person;
use Serendipity\Job\Serializer\SymfonySerializer;
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
                                    case '/dag':
                                    {
                                        $session->respond('Hello Swow');
                                        break;
                                    }
                                    ## serializable
                                    case '/serializable':
                                    {
                                        $person = new class() {
                                            private $age;
                                            private $name;
                                            private $sportsperson;
                                            private $createdAt;

                                            // Getters
                                            public function getName()
                                            {
                                                return $this->name;
                                            }

                                            public function getAge()
                                            {
                                                return $this->age;
                                            }

                                            public function getCreatedAt()
                                            {
                                                return $this->createdAt;
                                            }

                                            // Issers
                                            public function isSportsperson()
                                            {
                                                return $this->sportsperson;
                                            }

                                            // Setters
                                            public function setName($name)
                                            {
                                                $this->name = $name;
                                            }

                                            public function setAge($age)
                                            {
                                                $this->age = $age;
                                            }

                                            public function setSportsperson($sportsperson)
                                            {
                                                $this->sportsperson = $sportsperson;
                                            }

                                            public function setCreatedAt($createdAt)
                                            {
                                                $this->createdAt = $createdAt;
                                            }
                                        };
                                        $person->setName('foo');
                                        $person->setAge(99);
                                        $person->setSportsperson(false);
                                        $serializer = $this->container()->get(SymfonySerializer::class);
                                        $json       = $serializer->serialize($person);
                                        $this->stdoutLogger->debug(sprintf('Class Serializer returned[%s]#', $json));
                                        $object = $serializer->deserialize($json, $person::class);
                                        $this->stdoutLogger->debug(sprintf('Class Deserializer returned[%s]#', get_class($object)));
                                        $session->respond($json);
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
                        ## close session
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
