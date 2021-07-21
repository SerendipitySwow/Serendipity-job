<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Server;

use Carbon\Carbon;
use FastRoute\Dispatcher;
use GuzzleHttp\Client;
use Hyperf\Engine\Channel;
use Hyperf\Utils\Str;
use PDO;
use Psr\Http\Message\RequestInterface;
use Serendipity\Job\Console\ManageJobCommand;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Db\Command;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Kernel\Http\Response;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Kernel\Router\RouteCollector;
use Serendipity\Job\Kernel\Signature;
use Serendipity\Job\Kernel\Swow\ServerFactory;
use Serendipity\Job\Logger\LoggerFactory;
use Serendipity\Job\Middleware\AuthMiddleware;
use Serendipity\Job\Serializer\SymfonySerializer;
use Serendipity\Job\Util\Arr;
use Serendipity\Job\Util\Context;
use SerendipitySwow\Nsq\Nsq;
use Swow\Coroutine;
use Swow\Coroutine\Exception as CoroutineException;
use Swow\Http\Server;
use Swow\Http\Server\Request as SwowRequest;
use Swow\Http\Status;
use Swow\Socket\Exception;
use Swow\Socket\Exception as SocketException;
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
                                $response = $this->dispatcher($request);
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
    }

    protected function makeFastRoute(): void
    {
        $this->fastRouteDispatcher = simpleDispatcher(function (RouteCollector $router) {
            $router->get('/', static function (): Response {
                $response = new Response();

                return $response->text(file_get_contents(BASE_PATH . '/storage/task.php'));
            });
            /*
             * 创建应用
             */
            $router->post('/application/create', function (): Response {
                /**
                 * @var SwowRequest $request
                 */
                $appKey = Str::random();
                $secretKey = Str::random(32);
                $request = Context::get(RequestInterface::class);
                $params = json_decode($request->getBody()
                    ->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
                        $response->json([
                            'code' => 1,
                            'msg' => sprintf('Unknown Task [%s]#', $params['task_id']),
                            'data' => [],
                        ]);

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
                 * 创建任务
                 * dag or task
                 */
                $router->post('/task/create', function (): Response {
                    $response = new Response();
                    /**
                     * @var SwowRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $params = json_decode($request->getBody()
                        ->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
                    if (DB::fetch(sprintf(
                        "select count(*) from task where app_key = '%s' and task_no = '%s'",
                        $appKey,
                        $taskNo
                    ))) {
                        $json = [
                            'code' => 1,
                            'msg' => '请勿重复提交!',
                            'data' => [],
                        ];
                    } else {
                        $appKey = Arr::get($application, 'app_key');
                        $running = Carbon::parse($runtime)
                            ->lte(Carbon::now()
                            ->toDateTimeString()) ? 1 : 0;
                        $data = [
                            'app_key' => $appKey,
                            'task_no' => $taskNo,
                            'status' => $running,
                            'step' => Arr::get($application, 'step'),
                            'runtime' => $runtime,
                            'content' => $content,
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

                        $content = json_decode(Arr::get($data, 'content'), true, 512, JSON_THROW_ON_ERROR);
                        $serializerObject = make($content['class'], [
                            'identity' => $id,
                            'timeout' => Arr::get($data, 'timeout'),
                            'step' => Arr::get($data, 'step'),
                            'name' => Arr::get($data, 'name'),
                            'retryTimes' => 0,
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
                        $bool = $nsq->publish(ManageJobCommand::TOPIC_PREFIX . 'task', $json, $delay);
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
                     * @var SwowRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $swowResponse = new Response();
                    $params = json_decode($request->getBody()
                        ->getContents(), true, 512, JSON_THROW_ON_ERROR);
                    $client = new Client();
                    $config = $this->container()
                        ->get(ConfigInterface::class)
                        ->get('task_server');
                    $response = $client->get(
                        sprintf('%s:%s/%s', $config['host'], $config['port'], 'detail'),
                        [
                            'query' => ['coroutine_id' => $params['coroutine_id']],
                        ]
                    );

                    return $swowResponse->json(json_decode($response->getBody()
                        ->getContents(), true, 512, JSON_THROW_ON_ERROR));
                });
                /*
                 * 取消任务
                 */
                $router->post('/task/cancel', function () {
                    /**
                     * @var SwowRequest $request
                     */
                    $request = Context::get(RequestInterface::class);
                    $swowResponse = new Response();
                    $params = $request->getQueryParams();
                    $client = new Client();
                    $config = $this->container()
                        ->get(ConfigInterface::class)
                        ->get('task_server');
                    $response = $client->get(
                        sprintf('%s:%s/%s', $config['host'], $config['port'], 'cancel'),
                        [
                            'query' => ['coroutine_id' => $params['coroutine_id']],
                        ]
                    );

                    return $swowResponse->json(json_decode($response->getBody()
                        ->getContents(), true, 512, JSON_THROW_ON_ERROR));
                });
            });
        }, [
            'routeCollector' => RouteCollector::class,
        ]);
    }

    protected function dispatcher(SwowRequest $request): Response
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
                    $response = new Response();
                    $response->text(file_get_contents(BASE_PATH . '/storage/404.php'));
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    //$allowedMethods = $routeInfo[1];
                    $response = new Response();
                    $response->error(Status::NOT_ALLOWED, 'Method Not Allowed');
                    break;
                case Dispatcher::FOUND: // 找到对应的方法
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
                        } catch (Throwable $exception) {
                            $this->logger->error(serendipity_format_throwable($exception));
                            $response = new Response();
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
