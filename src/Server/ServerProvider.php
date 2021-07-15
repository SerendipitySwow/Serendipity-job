<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Server;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Hyperf\Engine\Channel;
use Psr\Http\Message\RequestInterface;
use Serendipity\Job\Console\ManageJobCommand;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Serendipity\Job\Logger\LoggerFactory;
use Serendipity\Job\Serializer\SymfonySerializer;
use Serendipity\Job\Util\Context;
use SerendipitySwow\Nsq\Nsq;
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
                            } catch (HttpException $exception) {
                                $session->error($exception->getCode(), $exception->getMessage());
                            }
                            if (!$request || !$request->getKeepAlive()) {
                                break;
                            }
                        }
                    } catch (Exception) {
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
                if (!$ret) {
                    $buffer->write(json_encode([
                        'code' => 1,
                        'msg' => sprintf('Unknown Task [%s]#', $params['task_id']),
                        'data' => [],
                    ], JSON_THROW_ON_ERROR));
                    $response->setBody($buffer);

                    return $response;
                }
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
                $bool ? $buffer->write(json_encode([
                    'code' => 0,
                    'msg' => 'Ok!',
                    'data' => [],
                ], JSON_THROW_ON_ERROR)) : $buffer->write(json_encode([
                    'code' => 1,
                    'msg' => '推送nsq失败!',
                    'data' => [],
                ], JSON_THROW_ON_ERROR));
                $response->setStatus(Status::OK);
                $response->setHeader('Server', 'Serendipity-Job');
                $response->setBody($buffer);

                return $response;
            });
            /*
             * 创建应用
             */
            $router->post('/application/create', function () {
            });
            /*
             * 创建任务
             * dag or task
             */
            $router->post('/task/create', function () {
            });
            /*
             * 查看任务详情
             */
            $router->post('/task/detail', function () {
            });
            /*
             * 取消任务
             */
            $router->post('/task/cancel', function () {
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
                    //$allowedMethods = $routeInfo[1];
                    $response = new SwowResponse();
                    $response->error(Status::NOT_ALLOWED, 'Method Not Allowed');
                    break;
                case Dispatcher::FOUND: // 找到对应的方法
                    [ , $handler, $vars ] = $routeInfo;
                    $response = call($handler, $vars);
                    break;
            }
            $channel->push($response);
        });

        return $channel->pop();
    }
}
