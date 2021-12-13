<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Server;

use Carbon\Carbon;
use DeviceDetector\DeviceDetector;
use FastRoute\Dispatcher;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Engine\Channel;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Str;
use PDO;
use Psr\Http\Message\RequestInterface;
use Serendipity\Job\Console\DagJobCommand;
use Serendipity\Job\Console\JobCommand;
use Serendipity\Job\Constant\Statistical;
use Serendipity\Job\Constant\Task;
use Serendipity\Job\Db\Command;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Kernel\Http\Request as SerendipityRequest;
use Serendipity\Job\Kernel\Http\Response;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Router\RouteCollector;
use Serendipity\Job\Kernel\Signature;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Serendipity\Job\Kernel\Xhprof\Xhprof;
use Serendipity\Job\Logger\LoggerFactory;
use Serendipity\Job\Middleware\AuthMiddleware;
use Serendipity\Job\Middleware\Exception\UnauthorizedException;
use Serendipity\Job\Nsq\Consumer\AbstractConsumer;
use Serendipity\Job\Serializer\SymfonySerializer;
use Serendipity\Job\Util\Arr;
use Serendipity\Job\Util\Context;
use Serendipity\Job\Util\Coroutine as SerendipitySwowCo;
use SerendipitySwow\Nsq\Nsq;
use Spatie\Emoji\Emoji;
use Swow\Coroutine\Exception as CoroutineException;
use Swow\Http\Exception as HttpException;
use Swow\Http\Server;
use Swow\Http\Server\Request as SwowRequest;
use Swow\Http\Status;
use Swow\Socket\Exception as SocketException;
use SwowCloud\Contract\LoggerInterface;
use SwowCloud\Contract\StdoutLoggerInterface;
use SwowCloud\Redis\Lua\Hash\Incr;
use Throwable;
use function FastRoute\simpleDispatcher;
use function Serendipity\Job\Kernel\serendipity_format_throwable;
use const Swow\Errno\EMFILE;
use const Swow\Errno\ENFILE;
use const Swow\Errno\ENOMEM;

/**
 * Class ServerProvider
 */
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
        $this->stdoutLogger->info(str_repeat(Emoji::flagsForFlagChina() . '  ', 10));
        $this->stdoutLogger->debug(sprintf('%s Serendipity-Job Start Successfully# %s', Emoji::manSurfing(), Emoji::rocket()));
        $this->makeFastRoute();
        while (true) {
            try {
                $connection = $server->acceptConnection();
                SerendipitySwowCo::create(function () use ($connection) {
                    try {
                        while (true) {
                            $time = microtime(true);
                            $request = null;
                            try {
                                Xhprof::startPoint();
                                /**
                                 * @var SerendipityRequest $request
                                 */
                                $request = $connection->recvHttpRequest(make(SerendipityRequest::class));
                                $response = $this->dispatcher($request);
                                $connection->sendHttpResponse($response);
                            } catch (Throwable $exception) {
                                if ($exception instanceof HttpException) {
                                    $connection->error($exception->getCode(), $exception->getMessage());
                                }
                                throw $exception;
                            } finally {
                                if ($request === null) {
                                    return;
                                }
                                if (env('DEBUG')) {
                                    /*@var LoggerInterface $logger */
                                    $logger = $this->container()
                                        ->get(LoggerFactory::class)
                                        ->get('request');
                                    // 日志
                                    $time = microtime(true) - $time;
                                    $debug = 'URI: ' . $request->getUri()->getPath() . PHP_EOL;
                                    $debug .= 'TIME: ' . $time * 1000 . 'ms' . PHP_EOL;
                                    if ($customData = $request->getCustomData()) {
                                        $debug .= 'DATA: ' . $customData . PHP_EOL;
                                    }
                                    $debug .= 'REQUEST: ' . $request->getRequestString() . PHP_EOL;
                                    if (isset($response)) {
                                        $debug .= 'RESPONSE: ' . $request->getResponseString($response) . PHP_EOL;
                                    }
                                    if (isset($exception) && $exception instanceof Throwable) {
                                        $debug .= 'EXCEPTION: ' . $exception->getMessage() . PHP_EOL;
                                    }

                                    $dd = new DeviceDetector(current($request->getHeader('User-Agent')));
                                    $dd->parse();
                                    /* @noinspection PhpStatementHasEmptyBodyInspection */
                                    if ($dd->isBot()) {
                                        //do something
                                    } else {
                                        $debug .= 'DEVICE: ' . $dd->getDeviceName() . '| BRAND_NAME: ' . $dd->getBrandName() . '| OS:' . $dd->getOs('version') . '| CLIENT:' . Json::encode($dd->getClient()) . PHP_EOL;
                                    }
                                    if ($time > 1) {
                                        $logger->error($debug);
                                    } else {
                                        $logger->info($debug);
                                    }
                                }

                                Xhprof::endPoint($connection, $request);
                            }
                            if (!$request->getKeepAlive()) {
                                break;
                            }
                        }
                    } catch (Throwable $throwable) {
                        $this->logger->error(serendipity_format_throwable($throwable));
                    } finally {
                        ## close session
                        $connection->close();
                    }
                });
            } catch (SocketException|CoroutineException $exception) {
                if (in_array($exception->getCode(), [EMFILE, ENFILE, ENOMEM], true)) {
                    sleep(1);
                } else {
                    break;
                }
            }
        }
    }

    protected function makeFastRoute(): void
    {
        $this->fastRouteDispatcher = simpleDispatcher(function (RouteCollector $router) {
            /*
             * 刷新应用签名
             */
            $router->post('/application/refresh-signature', function (): Response {
                /**
                 * @var SerendipityRequest $request
                 */
                $request = Context::get(RequestInterface::class);
                $params = $request->post();
                $response = new Response();
                if (!$application = DB::fetch(sprintf(
                    "select * from application where app_key = '%s' and secret_key = '%s'",
                    $params['app_key'],
                    $params['secret_key']
                ))) {
                    return $response->json([
                        'code' => 1,
                        'msg' => 'Unknown Application Key#',
                        'data' => [],
                    ]);
                }
                /**
                 * @var Signature $signature
                 */
                $signature = make(Signature::class, [
                    'options' => [
                        'signatureSecret' => $params['secret_key'],
                        'signatureApiKey' => $params['app_key'],
                    ],
                ]);
                $timestamps = (string) time();
                $nonce = $signature->generateNonce();
                $payload = md5(Arr::get($application, 'app_name'));
                $clientSignature = $signature->generateSignature(
                    $timestamps,
                    $nonce,
                    $payload,
                    $params['secret_key']
                );

                return $response->json([
                    'code' => 0,
                    'msg' => 'Ok!',
                    'data' => [
                        'nonce' => $nonce,
                        'timestamps' => $timestamps,
                        'signature' => $clientSignature,
                        'appKey' => Arr::get($application, 'app_key'),
                        'payload' => $payload,
                        'secretKey' => Arr::get($application, 'secret_key'),
                    ],
                ]);
            });
            /*
             * 创建应用
             */
            $router->post('/application/create', function (): Response {
                $appKey = Str::random();
                $secretKey = Str::random(32);
                /**
                 * @var SerendipityRequest $request
                 */
                $request = Context::get(RequestInterface::class);
                $params = $request->post();
                $data = [
                    'status' => 1,
                    'app_key' => $appKey,
                    'app_name' => Arr::get($params, 'appName'),
                    'secret_key' => $secretKey,
                    'step' => (int) Arr::get($params, 'step', 0),
                    'retry_total' => (int) Arr::get($params, 'retryTotal', 5),
                    'link_url' => Arr::get($params, 'linkUrl'),
                    'remark' => Arr::get($params, 'remark'),
                    'created_at' => Carbon::now()
                        ->toDateTimeString(),
                ];
                /**
                 * @var Command $command
                 */
                $command = make(Command::class);
                $command->insert('application', $data);
                $id = DB::run(function (PDO $PDO) use ($command): int {
                    $statement = $PDO->prepare($command->getSql());

                    $this->bindValues($statement, $command->getParams());

                    $statement->execute();

                    return (int) $PDO->lastInsertId();
                });

                /**
                 * @var Signature $signature
                 */
                $signature = make(Signature::class, [
                    'options' => [
                        'signatureSecret' => $secretKey,
                        'signatureApiKey' => $appKey,
                    ],
                ]);
                $timestamps = (string) time();
                $nonce = $signature->generateNonce();
                $payload = md5(Arr::get($params, 'appName'));
                $clientSignature = $signature->generateSignature(
                    $timestamps,
                    $nonce,
                    $payload,
                    $secretKey
                );
                $response = new Response();

                return $response->json($id ? [
                    'code' => 0,
                    'msg' => 'Ok!',
                    'data' => [
                        'nonce' => $nonce, 'timestamps' => $timestamps,
                        'signature' => $clientSignature,
                        'appKey' => $appKey,
                        'payload' => $payload,
                        'secretKey' => $secretKey,
                    ],
                ] : [
                    'code' => 1,
                    'msg' => '创建应用失败!',
                    'data' => [],
                ]);
            });
            $router->addMiddleware(AuthMiddleware::class, function (RouteCollector $router) {
                $router->post('/nsq/publish', function (): Response {
                    $response = new Response();
                    /**
                     * @var SerendipityRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $params = $request->post();
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
                        $response->json([
                            'code' => 1,
                            'msg' => sprintf('Unknown Task [%s]#', $params['task_id']),
                            'data' => [],
                        ]);

                        return $response;
                    }
                    $content = Json::decode($ret['content']);
                    $serializerObject = make($content['class'], [
                        'identity' => $ret['id'],
                        'timeout' => $ret['timeout'],
                        'step' => $ret['step'],
                        'name' => $ret['name'],
                        'retryTimes' => $ret['retry_times'],
                    ]);
                    $json = $serializer->serialize($serializerObject);
                    $json = Json::encode(array_merge([
                        'body' => Json::decode(
                            $json
                        ),
                    ], ['class' => $serializerObject::class]));
                    $delay = strtotime($ret['runtime']) - time();
                    if ($delay > 0) {
                        /**
                         * 加入延迟任务统计
                         *
                         * @var Incr $incr
                         */
                        $incr = make(Incr::class);
                        $incr->eval([Statistical::TASK_DELAY, 24 * 60 * 60]);
                    }
                    $bool = $nsq->publish(AbstractConsumer::TOPIC_PREFIX . JobCommand::TOPIC_SUFFIX, $json, $delay > 0 ? $delay : 0.0);

                    return $response->json($bool ? [
                        'code' => 0,
                        'msg' => 'Ok!',
                        'data' => [],
                    ] : [
                        'code' => 1,
                        'msg' => '推送nsq失败!',
                        'data' => [],
                    ]);
                });
                /*
                 * 投递dag任务
                 */
                $router->post('/task/dag', function (): Response {
                    $response = new Response();
                    /**
                     * @var SerendipityRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $params = $request->post();
                    if (!DB::fetch('select * from workflow where id = ? and status = ?  limit 1;', [$params['workflow_id'], Task::TASK_TODO])) {
                        $response->json([
                            'code' => 1,
                            'msg' => sprintf('Unknown Workflow [%s] Or Workflow Is Finished#', $params['workflow_id']),
                            'data' => [],
                        ]);

                        return $response;
                    }
                    $config = $this->container()
                        ->get(ConfigInterface::class)
                        ->get(sprintf('nsq.%s', 'default'));
                    /**
                     * @var Nsq $nsq
                     */
                    $nsq = make(Nsq::class, [$this->container(), $config]);
                    $bool = $nsq->publish(
                        AbstractConsumer::TOPIC_PREFIX . DagJobCommand::TOPIC_SUFFIX,
                        Json::encode([$params['workflow_id']])
                    );

                    $json = $bool ? [
                        'code' => 0,
                        'msg' => 'ok!',
                        'data' => ['workflowId' => (int) $params['workflow_id']],
                    ] : [
                        'code' => 1,
                        'msg' => 'Workflow Published Nsq Failed!',
                        'data' => [],
                    ];

                    return $response->json($json);
                });
                /*
                 * 创建任务
                 * task
                 */
                $router->post('/task/create', function (): Response {
                    $response = new Response();
                    /**
                     * @var SerendipityRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $params = $request->post();
                    $appKey = $request->getHeaderLine('app_key');
                    $application = $request->getHeader('application');
                    $taskNo = Arr::get($params, 'taskNo');
                    $content = Arr::get($params, 'content');
                    $timeout = Arr::get($params, 'timeout');
                    $name = Arr::get($params, 'name');

                    $runtime = Arr::get($params, 'runtime');
                    $runtime = $runtime ? Carbon::parse($runtime)
                        ->toDateTimeString() : Carbon::now()
                        ->toDateTimeString();
                    if (current(DB::fetch(sprintf(
                        "select count(*) from task where app_key = '%s' and task_no = '%s'",
                        $appKey,
                        $taskNo
                    ))) > 0) {
                        $json = [
                            'code' => 1,
                            'msg' => '请勿重复提交!',
                            'data' => [],
                        ];
                    } else {
                        $appKey = Arr::get($application, 'app_key');
                        /*
                        $running = Carbon::parse($runtime)
                            ->lte(Carbon::now()
                            ->toDateTimeString()) ? Task::TASK_ING : Task::TASK_TODO;
                        */
                        $data = [
                            'app_key' => $appKey,
                            'task_no' => $taskNo,
                            'status' => Task::TASK_TODO,
                            'step' => Arr::get($application, 'step'),
                            'retry_times' => 1,
                            'runtime' => $runtime,
                            'content' => is_array($content) ? Json::encode($content) : $content,
                            'timeout' => $timeout,
                            // $content  =  { "class": "\\Job\\SimpleJob\\","_params":{"startDate":"xx","endDate":"xxx"}},
                            'created_at' => Carbon::now()
                                ->toDateTimeString(),
                            'name' => $name,
                        ];
                        /**
                         * @var Command $command
                         */
                        $command = make(Command::class);
                        $command->insert('task', $data);
                        $id = DB::run(function (PDO $PDO) use ($command): int {
                            $statement = $PDO->prepare($command->getSql());

                            $this->bindValues($statement, $command->getParams());

                            $statement->execute();

                            return (int) $PDO->lastInsertId();
                        });
                        $delay = strtotime($runtime) - time();
                        $config = $this->container()
                            ->get(ConfigInterface::class)
                            ->get(sprintf('nsq.%s', 'default'));
                        /**
                         * @var Nsq $nsq
                         */
                        $nsq = make(Nsq::class, [$this->container(), $config]);
                        $serializer = $this->container()
                            ->get(SymfonySerializer::class);

                        $content = Json::decode(Arr::get($data, 'content'));
                        $serializerObject = make($content['class'], [
                            'identity' => $id,
                            'timeout' => Arr::get($data, 'timeout'),
                            'step' => Arr::get($data, 'step'),
                            'name' => Arr::get($data, 'name'),
                            'retryTimes' => 1,
                        ]);
                        $json = $serializer->serialize($serializerObject);
                        $json = Json::encode(array_merge([
                            'body' => Json::decode(
                                $json
                            ),
                        ], ['class' => $serializerObject::class]));
                        $bool = $nsq->publish(AbstractConsumer::TOPIC_PREFIX . JobCommand::TOPIC_SUFFIX, $json, $delay);
                        if ($delay > 0) {
                            /**
                             * 加入延迟任务统计
                             *
                             * @var Incr $incr
                             */
                            $incr = make(Incr::class);
                            $incr->eval([Statistical::TASK_DELAY, 24 * 60 * 60]);
                        }
                        $json = $bool ? [
                            'code' => 0,
                            'msg' => 'ok!',
                            'data' => ['taskId' => $id],
                        ] : [
                            'code' => 1,
                            'msg' => 'Unknown!',
                            'data' => [],
                        ];
                    }

                    return $response->json($json);
                });
                /*
                 * 查看任务详情
                 */
                $router->get('/task/detail', function (): Response {
                    /**
                     * @var SerendipityRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $swowResponse = new Response();
                    $params = $request->get();
                    $client = new Client();
                    $config = $this->container()
                        ->get(ConfigInterface::class)
                        ->get('task_server');
                    $response = $client->get(
                        sprintf('%s:%s/%s', $config['host'], $config['port'], 'detail'),
                        [
                            'query' => ['coroutine_id' => $params['coroutine_id'] ?? 0],
                        ]
                    );

                    return $swowResponse->json(Json::decode($response->getBody()
                        ->getContents()));
                });
                /*
                 * 取消任务
                 */
                $router->post('/task/cancel', function () {
                    /**
                     * @var SerendipityRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $swowResponse = new Response();
                    $params = $request->post();
                    $client = new Client();
                    $config = $this->container()
                        ->get(ConfigInterface::class)
                        ->get('task_server');
                    $response = $client->post(
                        sprintf('%s:%s/%s', $config['host'], $config['port'], 'cancel'),
                        [
                            RequestOptions::JSON => [
                                'coroutine_id' => $params['coroutine_id'],
                                'id' => $params['id'],
                            ],
                        ]
                    );

                    return $swowResponse->json(Json::decode($response->getBody()
                        ->getContents()));
                });
            });
        }, [
            'routeCollector' => RouteCollector::class,
        ]);
    }

    protected function dispatcher(SwowRequest $request): Response
    {
        $channel = new Channel();
        SerendipitySwowCo::create(function () use ($request, $channel) {
            SerendipitySwowCo::defer(function () {
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
                    $response = new Response();
                    $response->error(Status::NOT_FOUND, 'Not Found');
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    $response = new Response();
                    $response->error(Status::NOT_ALLOWED, 'Method Not Allowed');
                    break;
                case Dispatcher::FOUND:
                    [ , $handler, $vars ] = $routeInfo;
                    if (is_array($handler) && $handler['middlewares']) {
                        //middleware
                        /**
                         * @var AuthMiddleware $middleware
                         */
                        $middleware = $this->container()
                            ->get($handler['middlewares'][0]);
                        try {
                            $check = $middleware->process(Context::get(RequestInterface::class));
                            if (!$check) {
                                $response = new Response();
                                $response->error(Status::UNAUTHORIZED, 'UNAUTHORIZED');
                                break;
                            }
                            $response = call($handler[0], $vars);
                            break;
                        } catch (Throwable $exception) {
                            $response = new Response();
                            if ($exception instanceof UnauthorizedException) {
                                $response->error(Status::UNAUTHORIZED, 'UNAUTHORIZED');
                                break;
                            }
                            $this->logger->error(serendipity_format_throwable($exception));
                            $response->error(Status::INTERNAL_SERVER_ERROR);
                            break;
                        }
                    }
                    $response = call($handler[0], $vars);
                    break;
            }
            $channel->push($response);
        });

        return $channel->pop();
    }
}
