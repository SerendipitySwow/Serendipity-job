<?php
declare(strict_types = 1);

namespace Serendipity\Job\Server;

use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Dag\Dag;
use Serendipity\Job\Kernel\Dag\Vertex;
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
                                        $buffer = new Buffer();
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
                                        // return response
                                        \Serendipity\Job\Util\Coroutine::create(function () use ($session)
                                        {
                                            $session->respond('Hello Swow');
                                        });
                                        ## Dag
                                        $dag = new Dag();
                                        ## 除了使用闭包外还可以实现 Serendipity\Job\Kernel\Dag\Runner接口来定义.并通过 Vertex::of 将其转化为一个顶点。
                                        /**
                                         * class MyJob implements \Hyperf\Dag\Runner {
                                         * public function run($results = []) {
                                         * return 'hello';
                                         * }
                                         * }
                                         * \Hyperf\Dag\Vertex::of(new MyJob(), "greeting");
                                         */
                                        $a = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "A\n";
                                        });
                                        $b = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "B\n";
                                        });
                                        $c = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "C\n";
                                        });
                                        $d = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "D\n";
                                        });
                                        $e = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "E\n";
                                        });
                                        $f = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "F\n";
                                        });
                                        $g = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "G\n";
                                        });
                                        $h = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "H\n";
                                        });
                                        $i = Vertex::make(function ()
                                        {
                                            sleep(1);
                                            echo "I\n";
                                        });
                                        $dag->addVertex($a)
                                            ->addVertex($b)
                                            ->addVertex($c)
                                            ->addVertex($d)
                                            ->addVertex($e)
                                            ->addVertex($f)
                                            ->addVertex($g)
                                            ->addVertex($h)
                                            ->addVertex($i)
                                            ->addEdge($a, $b)
                                            ->addEdge($a, $c)
                                            ->addEdge($a, $d)
                                            ->addEdge($b, $h)
                                            ->addEdge($b, $e)
                                            ->addEdge($b, $f)
                                            ->addEdge($c, $f)
                                            ->addEdge($c, $g)
                                            ->addEdge($d, $g)
                                            ->addEdge($h, $i)
                                            ->addEdge($e, $i)
                                            ->addEdge($f, $i)
                                            ->addEdge($g, $i);
                                        $dag->run();

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
                                        $buffer = new Buffer();
                                        $buffer->write(file_get_contents(SERENDIPITY_JOB_PATH . '/storage/404.php'));
                                        $response = new Response();
                                        $response->setStatus(Status::NOT_FOUND);
                                        $response->setHeader('Server', 'Serendipity-Job');
                                        $response->setBody($buffer);
                                        $session->sendHttpResponse($response);
                                        ## clear buffer
                                        $buffer->clear();
                                        $this->stdoutLogger->debug(sprintf('Http Client Fd[%s] NotFound#', (string)$session->getFd()));
                                        break;
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
