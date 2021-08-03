<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Console;

use Carbon\Carbon;
use Exception;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Constant\Task;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\EventDispatcherInterface;
use Serendipity\Job\Contract\SerializerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Crontab\CrontabDispatcher;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Event\CrontabEvent;
use Serendipity\Job\Kernel\Http\Response;
use Serendipity\Job\Kernel\Provider\KernelProvider;
use Serendipity\Job\Nsq\Consumer\AbstractConsumer;
use Serendipity\Job\Nsq\Consumer\DagConsumer;
use Serendipity\Job\Nsq\Consumer\TaskConsumer;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Nsq;
use SerendipitySwow\Nsq\Result;
use Swow\Coroutine;
use Swow\Coroutine\Exception as CoroutineException;
use Swow\Http\Exception as HttpException;
use Swow\Http\Server as HttpServer;
use Swow\Http\Status as HttpStatus;
use Swow\Socket\Exception as SocketException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use const Swow\Errno\EMFILE;
use const Swow\Errno\ENFILE;
use const Swow\Errno\ENOMEM;

final class ManageJobCommand extends Command
{
    public static $defaultName = 'manage-job:start';

    protected const COMMAND_PROVIDER_NAME = 'Manage-Job';

    protected const TASK_TYPE = [
        'dag',
        'task',
    ];

    public const TOPIC_PREFIX = 'serendipity-job-';

    protected ?ConfigInterface $config = null;

    protected ?StdoutLoggerInterface $stdoutLogger = null;

    protected ?SerializerInterface $serializer = null;

    protected ?Nsq $subscriber = null;

    protected function configure(): void
    {
        $this
            ->setDescription('Start Manage Job')
            ->setDefinition([
                new InputOption(
                    'type',
                    't',
                    InputOption::VALUE_REQUIRED,
                    'Select the type of task to be performed (dag, task),',
                    'task'
                ),
                new InputOption(
                    'limit',
                    'l',
                    InputOption::VALUE_REQUIRED,
                    'Configure the number of coroutines to process tasks',
                    1
                ),
                new InputOption(
                    'host',
                    'host',
                    InputOption::VALUE_REQUIRED,
                    'Configure HttpServer host',
                    '127.0.0.1'
                ),
                new InputOption(
                    'port',
                    'p',
                    InputOption::VALUE_REQUIRED,
                    'Configure HttpServer port numbers',
                    9764
                ),
            ])
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> command consumes tasks

                        <info>php %command.full_name%</info>

                    Use the --limit option configure the number of coroutines to process tasks:
                        <info>php %command.full_name% --limit=10</info>
                    Use the --type option Select the type of task to be performed (dag, task),If you choose dag, limit is best configured to 1:
                        <info>php %command.full_name% --type=task</info>
                    Use the --host Configure HttpServer host:
                        <info>php %command.full_name% --host=127.0.0.1</info>
                    Use the --type Configure HttpServer port numbers:
                        <info>php %command.full_name% --port=9764</info>
                    EOF
            );
    }

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    public function handle(): int
    {
        $this->showLogo();
        $this->bootStrap();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        $this->stdoutLogger->info('Consumer Task Successfully Processed#');
        $limit = $this->input->getOption('limit');
        $type = $this->input->getOption('type');
        $port = (int) $this->input->getOption('port');
        $host = $this->input->getOption('host');
        if (!in_array($type, self::TASK_TYPE, true)) {
            $this->stdoutLogger->error('Invalid task parameters.');
            exit(1);
        }
        if ($limit !== null) {
            for ($i = 0; $i < $limit; $i++) {
                $this->subscribe($i, $type);
                $this->stdoutLogger->info(ucfirst($type) . 'Consumer#' . $i . ' start.');
            }
        }
        $this->makeServer($host, $port);

        return SymfonyCommand::SUCCESS;
    }

    protected function makeServer(string $host, int $port): void
    {
        $server = new HttpServer();
        $server->bind($host, $port)
            ->listen();
        while (true) {
            try {
                $session = $server->acceptSession();
                Coroutine::run(function () use ($session) {
                    try {
                        while (true) {
                            $request = null;
                            try {
                                $request = $session->recvHttpRequest();
                                switch ($request->getPath()) {
                                    case '/detail':
                                    {
                                        $params = $request->getQueryParams();
                                        $coroutine = Coroutine::get((int) $params['coroutine_id']);
                                        $data = [
                                            'state' => $coroutine?->getStateName(), //当前协程
                                            'trace_list' => json_encode($coroutine?->getTrace(), JSON_THROW_ON_ERROR), //协程函数调用栈
                                            'executed_file_name' => $coroutine?->getExecutedFilename(), //获取执行文件名
                                            'executed_function_name' => $coroutine?->getExecutedFunctionName(), //获取执行的函数名称
                                            'executed_function_line' => $coroutine?->getExecutedLineno(), //获得执行的文件行数
                                            'vars' => $coroutine?->getDefinedVars(), //获取定义的变量
                                            'round' => $coroutine?->getRound(), //获取协程切换次数
                                            'elapsed' => $coroutine?->getElapsed(), //获取协程运行的时间以便于分析统计或找出僵尸协程
                                        ];
                                        $response = new Response();
                                        $response->json([
                                            'code' => 0,
                                            'msg' => 'ok!',
                                            'data' => $data,
                                        ]);
                                        $session->sendHttpResponse($response);
                                        break;
                                    }
                                    case '/cancel':
                                        $params = json_decode($request->getBody()
                                            ->getContents(), true, 512, JSON_THROW_ON_ERROR);
                                        $coroutine = Coroutine::get((int) $params['coroutine_id']);
                                        $response = new Response();
                                        if (!$coroutine instanceof Coroutine) {
                                            $response->json([
                                                'code' => 1,
                                                'msg' => 'Unknown!',
                                                'data' => [],
                                            ]);
                                            $session->sendHttpResponse($response);
                                            break;
                                        }
                                        if ($coroutine === Coroutine::getCurrent()) {
                                            $session->respond(json_encode([
                                                'code' => 1,
                                                'msg' => '参数错误!',
                                                'data' => [],
                                            ], JSON_THROW_ON_ERROR));
                                            break;
                                        }
                                        if ($coroutine->getState() === $coroutine::STATE_LOCKED) {
                                            $response->json([
                                                'code' => 1,
                                                'msg' => 'coroutine block object locked	!',
                                                'data' => [],
                                            ]);
                                            $session->sendHttpResponse($response);
                                            break;
                                        }
                                        $coroutine->kill();
                                        DB::execute(sprintf(
                                            "update task set status  = %s,memo = '%s' where coroutine_id = %s and status = %s and id = %s",
                                            Task::TASK_CANCEL,
                                            sprintf(
                                                '客户度IP:%s取消了任务,请求时间:%s.',
                                                $session->getPeerAddress(),
                                                Carbon::now()
                                                    ->toDateTimeString()
                                            ),
                                            $params['coroutine_id'],
                                            Task::TASK_ING,
                                            $params['id']
                                        ));
                                        if ($coroutine->isAvailable()) {
                                            $response->json([
                                                'code' => 1,
                                                'msg' => 'Not fully killed, try again later...',
                                                'data' => [],
                                            ]);
                                        } else {
                                            $response->json([
                                                'code' => 0,
                                                'msg' => 'Killed',
                                                'data' => [],
                                            ]);
                                        }
                                        $session->sendHttpResponse($response);
                                        break;
                                    default:
                                    {
                                        $session->error(HttpStatus::NOT_FOUND);
                                    }
                                }
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

    protected function subscribe(int $i, string $type): void
    {
        Coroutine::run(
            function () use ($i, $type) {
                /**
                 * @var NSq $subscriber
                 */
                $subscriber = make(Nsq::class, [
                    $this->container,
                    $this->config->get(sprintf('nsq.%s', 'default')),
                ]);
                $consumer = match ($type) {
                    'task' => $this->makeConsumer(TaskConsumer::class, self::TOPIC_PREFIX . $type, 'Consumer'),
                    'dag' => $this->makeConsumer(DagConsumer::class, self::TOPIC_PREFIX . $type, 'Consumer')
                };
                $subscriber->subscribe(
                    self::TOPIC_PREFIX . $type,
                    ucfirst($type) . 'Consumer' . $i,
                    function (Message $message) use ($consumer, $i) {
                        try {
                            $result = $consumer->consume($message);
                        } catch (Throwable $error) {
                            //Segmentation fault
                            $this->stdoutLogger->error(sprintf(
                                'Consumer failed to consume %s,reason: %s,file: %s,line: %s',
                                'Consumer' . $i,
                                $error->getMessage(),
                                $error->getFile(),
                                $error->getLine()
                            ));
                            $result = Result::DROP;
                        }

                        return $result;
                    }
                );
            }
        );
    }

    protected function makeConsumer(
        string $class,
        string $topic,
        string $channel,
        string $redisPool = 'default'
    ): AbstractConsumer {
        /**
         * @var AbstractConsumer $consumer
         */
        $consumer = ApplicationContext::getContainer()
            ->get($class);
        $consumer->setTopic($topic);
        $consumer->setChannel($channel);
        $consumer->setRedisPool($redisPool);

        return $consumer;
    }

    protected function bootStrap(): void
    {
        KernelProvider::create(self::COMMAND_PROVIDER_NAME)
            ->bootApp();
        Coroutine::run(fn () => $this->dispatchCrontab());
    }

    protected function dispatchCrontab(): void
    {
        $this->container->get(EventDispatcherInterface::class)
            ->dispatch(
                new CrontabEvent(),
                CrontabEvent::CRONTAB_REGISTER
            );
        $this->container->get(CrontabDispatcher::class)
            ->handle();
    }
}
