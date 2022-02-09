<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Server;

use Carbon\Carbon;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\OperatingSystem;
use Exception;
use FastRoute\Dispatcher;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Engine\Channel;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coroutine as HyperfCo;
use Hyperf\Utils\Str;
use itbdw\Ip\IpLocation;
use PDO;
use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;
use Spatie\Emoji\Emoji;
use Swow\Coroutine\Exception as CoroutineException;
use Swow\Http\Exception as HttpException;
use Swow\Http\Server;
use Swow\Http\Server\Connection;
use Swow\Http\Server\Request as SwowRequest;
use Swow\Http\Status;
use Swow\Socket\Exception as SocketException;
use SwowCloud\Contract\LoggerInterface;
use SwowCloud\Contract\StdoutLoggerInterface;
use SwowCloud\Job\Console\DagJobCommand;
use SwowCloud\Job\Console\JobCommand;
use SwowCloud\Job\Constant\Statistical;
use SwowCloud\Job\Constant\Task;
use SwowCloud\Job\Db\Command;
use SwowCloud\Job\Db\DB;
use SwowCloud\Job\Kernel\Consul\ConsulAgent;
use SwowCloud\Job\Kernel\Http\Request as SwowCloudRequest;
use SwowCloud\Job\Kernel\Http\Response;
use SwowCloud\Job\Kernel\Provider\AbstractProvider;
use SwowCloud\Job\Kernel\Router\RouteCollector;
use SwowCloud\Job\Kernel\Signature;
use SwowCloud\Job\Kernel\Swow\ServerFactory;
use SwowCloud\Job\Kernel\Xhprof\Xhprof;
use SwowCloud\Job\Logger\LoggerFactory;
use SwowCloud\Job\Middleware\AuthMiddleware;
use SwowCloud\Job\Middleware\Exception\UnauthorizedException;
use SwowCloud\Job\Nsq\Consumer\AbstractConsumer;
use SwowCloud\Job\Serializer\SymfonySerializer;
use SwowCloud\Nsq\Nsq;
use SwowCloud\Redis\Lua\Hash\Incr;
use Throwable;
use function Chevere\Xr\throwableHandler;
use function FastRoute\simpleDispatcher;
use function SwowCloud\Job\Kernel\serendipity_format_throwable;
use function SwowCloud\Job\Kernel\serendipity_json_decode;
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

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Hyperf\Di\Exception\NotFoundException
     * @throws \JsonException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function bootApp(): void
    {
        HyperfCo::create(function () {
            try {
                throwableHandler(new \RuntimeException('1111'));
            } catch (Throwable $throwable) {
                dd($throwable);
            }
        });
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
        $this->stdoutLogger->debug(sprintf('%s SwowCloud-Job Start Successfully# %s', Emoji::manSurfing(), Emoji::rocket()));
        $this->makeFastRoute();
        while (true) {
            try {
                $connection = $server->acceptConnection();
                HyperfCo::create(function () use ($connection) {
                    try {
                        while (true) {
                            $request = null;
                            try {
                                /**
                                 * @var SwowCloudRequest $request
                                 */
                                $request = $connection->recvHttpRequest(make(SwowCloudRequest::class));
                                $response = $this->dispatcher($request, $connection);
                                $connection->sendHttpResponse($response);
                            } catch (HttpException $exception) {
                                $connection->error($exception->getCode(), $exception->getMessage());
                            }
                            if (!$request || !$request->getKeepAlive()) {
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

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     * @noinspection PhpComplexFunctionInspection
     */
    protected function makeFastRoute(): void
    {
        $this->fastRouteDispatcher = simpleDispatcher(function (RouteCollector $router) {
            /*
             * 刷新应用签名
             */
            $router->post('/application/refresh-signature', function (): Response {
                /**
                 * @var SwowCloudRequest $request
                 */
                $request = Context::get(RequestInterface::class);
                $params = $request->post();
                $response = new Response();
                if (!$application = DB::fetch(
                    sprintf(
                        "select * from application where app_key = '%s' and secret_key = '%s'",
                        $params['app_key'],
                        $params['secret_key']
                    )
                )) {
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
                 * @var SwowCloudRequest $request
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
                    /* @var  \SwowCloud\Job\Db\PDOConnection $this */
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

                return (new Response())->json(
                    $id ? [
                        'code' => 0,
                        'msg' => 'Ok!',
                        'data' => [
                            'nonce' => $nonce,
                            'timestamps' => $timestamps,
                            'signature' => $clientSignature,
                            'appKey' => $appKey,
                            'payload' => $payload,
                            'secretKey' => $secretKey,
                        ],
                    ] : [
                        'code' => 1,
                        'msg' => '创建应用失败!',
                        'data' => [],
                    ]
                );
            });
            $router->addMiddleware(AuthMiddleware::class, function (RouteCollector $router) {
                $router->post('/nsq/publish', function (): Response {
                    $response = new Response();
                    /**
                     * @var SwowCloudRequest $request
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
                    $content = serendipity_json_decode($ret['content']);
                    $serializerObject = make($content['class'], [
                        'identity' => $ret['id'],
                        'timeout' => $ret['timeout'],
                        'step' => $ret['step'],
                        'name' => $ret['name'],
                        'retryTimes' => $ret['retry_times'],
                    ]);
                    $json = $serializer->serialize($serializerObject);
                    $json = Json::encode(
                        array_merge([
                            'body' => serendipity_json_decode(
                                $json
                            ),
                        ], ['class' => $serializerObject::class])
                    );
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

                    return $response->json(
                        $bool ? [
                            'code' => 0,
                            'msg' => 'Ok!',
                            'data' => [],
                        ] : [
                            'code' => 1,
                            'msg' => '推送nsq失败!',
                            'data' => [],
                        ]
                    );
                });
                /*
                 * 投递dag任务
                 */
                $router->post('/task/dag', function (): Response {
                    $response = new Response();
                    /**
                     * @var SwowCloudRequest $request
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
                     * @var SwowCloudRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $params = $request->post();
                    $appKey = $request->getHeaderLine('app_key');
                    $application = $request->getHeader('application');
                    $taskNo = Arr::get($params, 'taskNo', Uuid::uuid4()->toString());
                    $content = Arr::get($params, 'content');
                    $timeout = Arr::get($params, 'timeout');
                    $name = Arr::get($params, 'name');

                    $runtime = Arr::get($params, 'runtime');
                    $runtime = $runtime ? Carbon::parse($runtime)
                        ->toDateTimeString() : Carbon::now()
                        ->toDateTimeString();
                    if (current(
                        DB::fetch(
                            sprintf(
                                "select count(*) from task where app_key = '%s' and task_no = '%s'",
                                $appKey,
                                $taskNo
                            )
                        )
                    ) > 0) {
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

                        $content = serendipity_json_decode(Arr::get($data, 'content'));
                        $serializerObject = make($content['class'], [
                            'identity' => $id,
                            'timeout' => Arr::get($data, 'timeout'),
                            'step' => Arr::get($data, 'step'),
                            'name' => Arr::get($data, 'name'),
                            'retryTimes' => 1,
                        ]);
                        $json = $serializer->serialize($serializerObject);
                        $json = Json::encode(
                            array_merge([
                                'body' => serendipity_json_decode(
                                    $json
                                ),
                            ], ['class' => $serializerObject::class])
                        );
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
                 *
                 */
                $router->get('/task/detail', function (): Response {
                    /**
                     * @var SwowCloudRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $swowResponse = new Response();
                    $params = $request->get();

                    $consulAgent = $this->container()->get(ConsulAgent::class);
                    $service = $consulAgent->service($params['service_id'])->json();

                    try {
                        $client = new Client();
                        $response = $client->get(
                            sprintf('%s:%s/%s', $service['Address'], $service['Port'], 'detail'),
                            [
                                'query' => [
                                    'coroutine_id' => $params['coroutine_id'] ?? 0,
                                    'task_id' => $params['id'] ?? 0,
                                ],
                            ]
                        );

                        return $swowResponse->json(
                            serendipity_json_decode(
                                $response->getBody()
                                    ->getContents()
                            )
                        );
                    } catch (Exception $exception) {
                        throw new HttpException($exception->getCode(), $exception->getMessage());
                    }
                });
                /*
                 * 取消任务
                 */
                $router->post('/task/cancel', function () {
                    /**
                     * @var SwowCloudRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $swowResponse = new Response();
                    $params = $request->post();
                    $client = new Client();
                    $consulAgent = $this->container()->get(ConsulAgent::class);
                    $service = $consulAgent->service($params['service_id'])->json();
                    try {
                        $response = $client->post(
                            sprintf('%s:%s/%s', $service['Address'], $service['Port'], 'cancel'),
                            [
                                RequestOptions::JSON => [
                                    'coroutine_id' => $params['coroutine_id'],
                                    'id' => $params['id'],
                                ],
                            ]
                        );

                        return $swowResponse->json(
                            serendipity_json_decode(
                                $response->getBody()
                                    ->getContents()
                            )
                        );
                    } catch (Exception $exception) {
                        throw new HttpException($exception->getCode(), $exception->getMessage());
                    }
                });
            });
        }, [
            'routeCollector' => RouteCollector::class,
        ]);
    }

    protected function dispatcher(SwowRequest $request, Connection $connection): Response
    {
        $channel = new Channel();
        HyperfCo::create(function () use ($request, $channel, $connection) {
            Xhprof::startPoint();
            $time = microtime(true);
            HyperfCo::defer(static function () {
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
                    [, $handler, $vars] = $routeInfo;

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
                            if ($exception instanceof HttpException) {
                                $response->error($exception->getCode(), $exception->getMessage());
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
            $this->debug($time, $request, $response, $connection);
            $channel->push($response);
        });

        return $channel->pop();
    }

    /**
     * @param $time
     *
     * @throws \JsonException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function debug($time, SwowRequest|SwowCloudRequest $request, Response $response, Connection $connection): void
    {
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

            /*
             $command = make(Command::class);
             $command->insert('request_log', [
             'application_name' => (string) Arr::get($request->getHeader('application'), 'application_name', 'Unknown'),
             'app_key' => current($request->getHeader('app_key')),
             'ip' => $connection->getPeerAddress(),
             'ip_location' => Arr::get(IpLocation::getLocation($connection->getPeerAddress()), 'city'),
             'os' => OperatingSystem::getOsFamily($dd->getOs('name')) ?? 'Unknown',
             'request_info' => $debug,
             'request_time' => Carbon::now()->toDateTimeString(),
             ]);
             DB::run(function (PDO $PDO) use ($command) {
             $statement = $PDO->prepare($command->getSql());

             $this->bindValues($statement, $command->getParams());

             $statement->execute();
             });
            */
        }

        Xhprof::endPoint($connection, $request);
    }
}
