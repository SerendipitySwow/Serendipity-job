<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Server;

use Carbon\Carbon;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Hyperf\Engine\Channel;
use Psr\Http\Message\RequestInterface;
use Serendipity\Job\Console\ManageJobCommand;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Dag\Task\Task1;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Dingtalk\DingTalk;
use Serendipity\Job\Event\UpdateJobEvent;
use Serendipity\Job\Kernel\Dag\Dag;
use Serendipity\Job\Kernel\Dag\Vertex;
use Serendipity\Job\Kernel\Lock\RedisLock;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Serendipity\Job\Logger\LoggerFactory;
use Serendipity\Job\Serializer\SymfonySerializer;
use Serendipity\Job\Util\Context;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Nsq;
use SerendipitySwow\Nsq\Result;
use Swow\Coroutine;
use Swow\Coroutine\Exception as CoroutineException;
use Swow\Http\Buffer;
use Swow\Http\Server;
use Swow\Http\Server\Request as SwowRequest;
use Swow\Http\Server\Response as SwowResponse;
use Swow\Http\Server\Session;
use Swow\Http\Status;
use Swow\Signal;
use Swow\Socket\Exception;
use Swow\Socket\Exception as SocketException;
use function FastRoute\simpleDispatcher;
use const Swow\Errno\EMFILE;
use const Swow\Errno\ENFILE;
use const Swow\Errno\ENOMEM;

class ServerProvider extends AbstractProvider
{
    protected StdoutLoggerInterface $stdoutLogger;

    protected LoggerInterface $logger;

    protected Dispatcher $fastRouteDispatcher;

    public function bootApp(): void
    {
        /**
         * @var Server $server
         */
        $server = $this->container()
            ->make(ServerFactory::class)
            ->start();
        $this->stdoutLogger = $this->container()
            ->get(StdoutLoggerInterface::class);
        $this->logger = $this->container()
            ->get(LoggerFactory::class)
            ->get();
        $this->stdoutLogger->debug('Serendipity-Job Start Successfully#');
        $this->makeFastRoute();

        while (true) {
            try {
                $session = $server->acceptSession();
                Coroutine::run(function () use ($session) {
                    try {
                        while (true) {
                            if (!$session->isEstablished()) {
                                break;
                            }
                            $request = null;
                            try {
                                $request = $session->recvHttpRequest();
                                $response = $this->dispatcher($request, $session);
                                $session->sendHttpResponse($response);
//                                switch ($request->getPath()) {
//                                    case '/':
//                                    {
//                                        $buffer = new Buffer();
//                                        $buffer->write(file_get_contents(BASE_PATH . '/storage/task.php'));
//                                        $response = new SwowResponse();
//                                        $response->setStatus(Status::OK);
//                                        $response->setHeader('Server', 'Serendipity-Job');
//                                        $response->setBody($buffer);
//                                        $session->sendHttpResponse($response);
//                                        ## clear buffer
//                                        $buffer->clear();
//                                        $this->stdoutLogger->debug(sprintf(
//                                            'Http Client Fd[%s] Debug#',
//                                            (string) $session->getFd()
//                                        ));
//                                        break;
//                                    }
//                                    case '/db':
//                                    {
//                                        $content = [
//                                            'class' => Task1::class,
//                                            'params' => [
//                                                'startDate' => Carbon::now()
//                                                    ->subDays(10)
//                                                    ->toDateString(),
//                                                'endDate' => Carbon::now()
//                                                    ->toDateString(),
//                                            ],
//                                        ];
//                                        echo json_encode($content);
////                                        $db = $this->container()->get(DB::class);
////                                       $res = $db->query('select * from  `task`');
//                                        $tasks = DB::query(
//                                            'select `task_id` from vertex_edge where workflow_id = ?;',
//                                            [1]
//                                        );
//                                        $task = DB::query(
//                                            'select * from task where id in (?);',
//                                            [implode(',', array_column($tasks, 'task_id'))]
//                                        );
//                                        $session->respond(json_encode($task, JSON_THROW_ON_ERROR));
//                                        break;
//                                    }
//                                    case '/event':
//                                    {
//                                        $event = new UpdateJobEvent(1, 2);
//                                        $this->container()
//                                            ->get(EventDispatcherInterface::class)
//                                            ->dispatch($event, UpdateJobEvent::UPDATE_JOB);
//                                        break;
//                                    }
//                                    case '/ding':
//                                    {
//                                        make(DingTalk::class)
//                                            ->at(['13888888888'], true)
//                                            ->text('我就是我,@13888888888 是不一样的烟火');
//                                        break;
//                                    }
//                                    case '/lock':
//                                    {
//                                        $redis = new \Redis();
//                                        $redis->connect('127.0.0.1', 6379);
//                                        $lock = new RedisLock($redis);
//                                        if ($lock->lock('test')) {
//                                            $this->stdoutLogger->debug('test locked#');
//                                            $lock->unlock('test');
//                                            $this->stdoutLogger->debug('test unlocked#' . date('Y-m-d H:i:s'));
//                                            $session->respond('Hello Lock!');
//                                            break;
//                                        }
//                                        $this->stdoutLogger->error('test unlocked#error' . Coroutine::getCurrent()
//                                            ->getId());
//                                        $session->respond('Hello Lock failed!');
//                                        break;
//                                    }
//
//                                    case '/nsq/publish':
//                                    {
//                                        //TODO 2021-06-27 测试nsq 推送任务
//                                        $config = $this->container()
//                                            ->get(ConfigInterface::class)
//                                            ->get(sprintf('nsq.%s', 'default'));
//                                        /**
//                                         * @var Nsq $nsq
//                                         */
//                                        $nsq = make(Nsq::class, [$this->container(), $config]);
//                                        $serializer = $this->container()
//                                            ->get(SymfonySerializer::class);
//                                        $ret = DB::fetch('select * from task where id = 1 limit 1;');
//                                        $content = json_decode($ret['content'], true, 512, JSON_THROW_ON_ERROR);
//                                        $serializerObject = make($content['class'], [
//                                            'identity' => $ret['id'],
//                                            'timeout' => $ret['timeout'],
//                                            'step' => $ret['step'],
//                                            'name' => $ret['name'],
//                                            'retryTimes' => $ret['retry_times'],
//                                        ]);
//                                        $json = $serializer->serialize($serializerObject);
//                                        $json = json_encode(array_merge([
//                                            'body' => json_decode(
//                                                $json,
//                                                true,
//                                                512,
//                                                JSON_THROW_ON_ERROR
//                                            ),
//                                        ], ['class' => $serializerObject::class]), JSON_THROW_ON_ERROR);
//                                        $nsq->publish(ManageJobCommand::TOPIC_PREFIX . 'task', $json);
//
//                                        $session->respond('Hello Nsq!');
//                                        break;
//                                    }
//                                    case '/nsq/subscribe':
//                                    {
//                                        $config = $this->container()
//                                            ->get(ConfigInterface::class)
//                                            ->get(sprintf('nsq.%s', 'default'));
//                                        /**
//                                         * @var Nsq $nsq
//                                         */
//                                        $nsq = make(Nsq::class, [$this->container(), $config]);
//
//                                        Coroutine::run(function () use ($nsq) {
//                                            $nsq->subscribe('test', 'v2', function (Message $data) {
//                                                $this->stdoutLogger->error('Subscribe ' . $data->getBody() . PHP_EOL);
//
//                                                return Result::ACK;
//                                            });
//                                        });
//                                        $session->respond('Hello Nsq-subscribe!');
//                                        break;
//                                    }
//                                    case '/dag':
//                                    {
//                                        // return response
//                                        \Serendipity\Job\Util\Coroutine::create(function () use ($session) {
//                                            $session->respond('Hello Swow');
//                                        });
//                                        ## Dag
//                                        $dag = new Dag();
//                                        ## 除了使用闭包外还可以实现 Serendipity\Job\Kernel\Dag\Runner接口来定义.并通过 Vertex::of 将其转化为一个顶点。
//                                        /**
//                                         * class MyJob implements \Hyperf\Dag\Runner {
//                                         * public function run($results = []) {
//                                         * return 'hello';
//                                         * }
//                                         * }
//                                         * \Hyperf\Dag\Vertex::of(new MyJob(), "greeting");
//                                         */
//                                        $a = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "A\n";
//
//                                            return 'A';
//                                        }, 2000);
//                                        $b = Vertex::make(function ($results) use ($a) {
//                                            sleep(1);
//                                            echo "B\n";
//                                            echo $results[$a->key] . PHP_EOL;
//
//                                            return 'B';
//                                        }, 2000);
//                                        $c = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "C\n";
//
//                                            return "C\n";
//                                        }, 2000);
//                                        $d = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "D\n";
//
//                                            return "D\n";
//                                        }, 2000);
//                                        $e = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "E\n";
//
//                                            return "E\n";
//                                        }, 2000);
//                                        $f = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "F\n";
//
//                                            return "F\n";
//                                        }, 2000);
//                                        $g = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "G\n";
//
//                                            return "G\n";
//                                        }, 2000);
//                                        $h = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "H\n";
//
//                                            return "H\n";
//                                        }, 2000);
//                                        $i = Vertex::make(function () {
//                                            sleep(1);
//                                            echo "I\n";
//
//                                            return "I\n";
//                                        }, 2000);
//                                        $dag->addVertex($a)
//                                            ->addVertex($b)
//                                            ->addVertex($c)
//                                            ->addVertex($d)
//                                            ->addVertex($e)
//                                            ->addVertex($f)
//                                            ->addVertex($g)
//                                            ->addVertex($h)
//                                            ->addVertex($i)
//                                            ->addEdge($a, $b)
//                                            ->addEdge($a, $c)
//                                            ->addEdge($a, $d)
//                                            ->addEdge($b, $h)
//                                            ->addEdge($b, $e)
//                                            ->addEdge($b, $f)
//                                            ->addEdge($c, $f)
//                                            ->addEdge($c, $g)
//                                            ->addEdge($d, $g)
//                                            ->addEdge($h, $i)
//                                            ->addEdge($e, $i)
//                                            ->addEdge($f, $i)
//                                            ->addEdge($g, $i);
//                                        $arr = $dag->run();
//                                        break;
//                                    }
//                                    ## serializable
//                                    case '/serializable':
//                                    {
//                                        $person = new class() {
//                                            private $age;
//
//                                            private $name;
//
//                                            private $sportsperson;
//
//                                            private $createdAt;
//
//                                            // Getters
//                                            public function getName()
//                                            {
//                                                return $this->name;
//                                            }
//
//                                            public function getAge()
//                                            {
//                                                return $this->age;
//                                            }
//
//                                            public function getCreatedAt()
//                                            {
//                                                return $this->createdAt;
//                                            }
//
//                                            // Issers
//                                            public function isSportsperson()
//                                            {
//                                                return $this->sportsperson;
//                                            }
//
//                                            // Setters
//                                            public function setName($name)
//                                            {
//                                                $this->name = $name;
//                                            }
//
//                                            public function setAge($age)
//                                            {
//                                                $this->age = $age;
//                                            }
//
//                                            public function setSportsperson($sportsperson)
//                                            {
//                                                $this->sportsperson = $sportsperson;
//                                            }
//
//                                            public function setCreatedAt($createdAt)
//                                            {
//                                                $this->createdAt = $createdAt;
//                                            }
//                                        };
//                                        $person->setName('foo');
//                                        $person->setAge(99);
//                                        $person->setSportsperson(false);
//                                        $serializer = $this->container()
//                                            ->get(SymfonySerializer::class);
//                                        $json = $serializer->serialize($person);
//                                        $this->stdoutLogger->debug(sprintf(
//                                            'Class Serializer returned[%s]#',
//                                            $json
//                                        ));
//                                        $object = $serializer->deserialize($json, $person::class);
//                                        $this->stdoutLogger->debug(sprintf(
//                                            'Class Deserializer returned[%s]#',
//                                            get_class($object)
//                                        ));
//                                        $session->respond($json);
//                                        break;
//                                    }
//                                    case '/echo':
//                                    {
//                                        $session->respond($request->getBodyAsString());
//                                        break;
//                                    }
//                                    default:
//                                    {
//                                        $buffer = new Buffer();
//                                        $buffer->write(file_get_contents(BASE_PATH . '/storage/404.php'));
//                                        $response = new SwowResponse();
//                                        $response->setStatus(Status::NOT_FOUND);
//                                        $response->setHeader('Server', 'Serendipity-Job');
//                                        $response->setBody($buffer);
//                                        $session->sendHttpResponse($response);
//                                        ## clear buffer
//                                        $buffer->clear();
//                                        $this->stdoutLogger->debug(sprintf(
//                                            'Http Client Fd[%s] NotFound#',
//                                            (string) $session->getFd()
//                                        ));
//                                        break;
//                                    }
//                                }
                            } catch (HttpException $exception) {
                                $session->error($exception->getCode(), $exception->getMessage());
                            }
                            if (!$request || !$request->getKeepAlive()) {
                                break;
                            }
                        }
                    } catch (Exception $exception) {
                        // you can log error here
                    } finally {
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
        /*
        当并发量比较大时,会阻塞该协程.
        监听协程退出
        $exited = new Channel();
        Signal::wait(Signal::INT);

        \Hyperf\Engine\Coroutine::create(fn () => $exited->close());
        \Hyperf\Engine\Coroutine::create(function () use ($exited) {
            while (true) {
                if ($exited->isClosing()) {
                    $tryAgain = false;
                    do {
                        $this->stdoutLogger->debug('Kill Start ============================');
                        foreach (Coroutine::getAll() as $coroutine) {
                            if ($coroutine === Coroutine::getCurrent()) {
                                continue;
                            }
                            if ($coroutine->getState() === $coroutine::STATE_LOCKED) {
                                continue;
                            }
                            echo "Kill {$coroutine->getId()}..." . PHP_EOL;
                            $coroutine->kill();
                            if ($coroutine->isAvailable()) {
                                echo 'Not fully killed, try again later...' . PHP_EOL;
                                $tryAgain = true;
                            } else {
                                echo 'Killed' . PHP_EOL;
                            }
                        }
                    } while ($tryAgain);
                    echo 'All coroutines has been killed' . PHP_EOL;
                    break;
                }
            }
        });
        */
    }

    protected function makeFastRoute(): void
    {
        $this->fastRouteDispatcher = simpleDispatcher(function (RouteCollector $router) {
            $router->get('/', static function (): SwowResponse {
                /**
                 * @var Request $request
                 */
                $request = Context::get(RequestInterface::class);
                $response = new SwowResponse();
                $buffer = new Buffer();
                $buffer->write(file_get_contents(BASE_PATH . '/storage/task.php'));
                $response->setStatus(Status::OK);
                $response->setHeader('Server', 'Serendipity-Job');
                $response->setBody($buffer);

                return $response;
            });
            $router->post('/nsq/publish', function (): SwowResponse {
                $response = new SwowResponse();
                $buffer = new Buffer();
                /**
                 * @var SwowRequest $request
                 */
                $request = Context::get(RequestInterface::class);
                $params = json_decode($request->getBody()
                    ->getContents(), true, 512, JSON_THROW_ON_ERROR);
                $config = $this->container()
                    ->get(ConfigInterface::class)
                    ->get(sprintf('nsq.%s', 'default'));
                /**
                 * @var Nsq $nsq
                 */
                $nsq = make(Nsq::class, [$this->container(), $config]);
                $serializer = $this->container()
                    ->get(SymfonySerializer::class);
                $ret = DB::fetch('select * from task where id = ? limit 1;', [$params['task_id']]);
                $content = json_decode($ret['content'], true, 512, JSON_THROW_ON_ERROR);
                $serializerObject = make($content['class'], [
                    'identity' => $ret['id'],
                    'timeout' => $ret['timeout'],
                    'step' => $ret['step'],
                    'name' => $ret['name'],
                    'retryTimes' => $ret['retry_times'],
                ]);
                $json = $serializer->serialize($serializerObject);
                $json = json_encode(array_merge([
                    'body' => json_decode(
                        $json,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    ),
                ], ['class' => $serializerObject::class]), JSON_THROW_ON_ERROR);
                $bool = $nsq->publish(ManageJobCommand::TOPIC_PREFIX . 'task', $json);
                if ($bool) {
                    $buffer->write(json_encode([
                        'code' => 0,
                        'msg' => 'Ok!',
                        'data' => [],
                    ], JSON_THROW_ON_ERROR));
                } else {
                    $buffer->write(json_encode([
                        'code' => 1,
                        'msg' => '推送nsq失败!',
                        'data' => [],
                    ], JSON_THROW_ON_ERROR));
                }
                $response->setStatus(Status::OK);
                $response->setHeader('Server', 'Serendipity-Job');
                $response->setBody($buffer);

                return $response;
            });
        });
    }

    protected function dispatcher(SwowRequest $request, Session $session): SwowResponse
    {
        $channel = new Channel();
        Coroutine::run(function () use ($request, $channel) {
            \Swow\defer(function () {
                Context::destroy(RequestInterface::class);
            });
            Context::set(RequestInterface::class, $request);
            $uri = $request->getPath();
            $method = $request->getMethod();
            if (false !== $pos = strpos($uri, '?')) {
                $uri = substr($uri, 0, $pos);
            }
            $uri = rawurldecode($uri);
            $routeInfo = $this->fastRouteDispatcher->dispatch($method, $uri);
            $response = null;
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    $buffer = new Buffer();
                    $buffer->write(file_get_contents(BASE_PATH . '/storage/404.php'));
                    $response = new SwowResponse();
                    $response->setStatus(Status::NOT_FOUND);
                    $response->setHeader('Server', 'Serendipity-Job');
                    $response->setBody($buffer);
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    $allowedMethods = $routeInfo[1];
                    // ... 405 Method Not Allowed  方法不允许
                    break;
                case Dispatcher::FOUND: // 找到对应的方法
                    [ $uri,$handler, $vars ] = $routeInfo;
                    // ... call $handler with $vars // 调用处理函数
                    $response = call($handler, $vars);
                    break;
            }
            $channel->push($response);
        });

        return $channel->pop();
    }
}
