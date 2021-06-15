<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Console;

use Psr\Container\ContainerInterface;
use Redis;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\JobInterface;
use Serendipity\Job\Contract\SerializerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Lock\RedisLock;
use Serendipity\Job\Kernel\Provider\KernelProvider;
use Serendipity\Job\Serializer\SymfonySerializer;
use Serendipity\Job\Util\Waiter;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Nsq;
use SerendipitySwow\Nsq\Result;
use Swow\Coroutine;
use Symfony\Component\Console\Input\InputOption;

final class ConsumeJobCommand extends Command
{
    public static $defaultName = 'scheduler:consume';

    protected const COMMADN_PROVIDER_NAME = 'Consumer-Job-';

    protected const TASK_TYPE = [
        'dag',
        'task',
    ];

    public const TOPIC_PREFIX = 'serendipity-job';

    protected ?ConfigInterface $config = null;

    protected ?StdoutLoggerInterface $stdoutLogger = null;

    protected ?SerializerInterface $serializer = null;

    protected function configure(): void
    {
        $this
            ->setDescription('Consumes tasks')
            ->setDefinition([
                new InputOption(
                    'type',
                    't',
                    InputOption::VALUE_REQUIRED,
                    'Select the type of task to be performed (dag, task)',
                    'task'
                ),
                new InputOption(
                    'limit',
                    'l',
                    InputOption::VALUE_REQUIRED,
                    'Configure the number of coroutines to process tasks',
                    1
                ),
            ])
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> command consumes tasks

                        <info>php %command.full_name%</info>

                    Use the --limit option configure the number of coroutines to process tasks:
                        <info>php %command.full_name% --limit=10</info>
                    Use the --type option Select the type of task to be performed (dag, task):
                        <info>php %command.full_name% --limit=10</info>
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
        $this->bootStrap();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        $this->stdoutLogger->info('Consumer Task Successfully Processed#');
        $this->serializer = $this->container
            ->get(SymfonySerializer::class);
        $limit = $this->input->getOption('limit');
        $type = $this->input->getOption('type');
        if (!in_array($type, self::TASK_TYPE, true)) {
            $this->stdoutLogger->error('Invalid task parameters.');
            exit();
        }
        if ($limit !== null) {
            $config = $this->config
                ->get('nsq.default');
            for ($i = 0; $i < $limit; $i++) {
                Coroutine::run(
                    function () use ($config, $i, $type) {
                    /**
                     * @var Nsq $nsq
                     */
                    $nsq = make(Nsq::class, [$this->container, $config]);
                    $redis = ( new Redis() );
                    $redis->connect(
                        $this->config->get('redis.default.host'),
                        $this->config->get('redis.default.port')
                    );
                    /**
                     * @var RedisLock $lock
                     */
                    $lock = make(RedisLock::class, [
                        $redis,
                    ]);
                    switch ($type) {
                        case 'task':
                            $nsq->subscribe(
                                self::TOPIC_PREFIX . $type,
                                sprintf('Consumerd%s', $i),
                                function (Message $data) use ($lock) {
                                    /**
                                     * @var JobInterface $job
                                     */
                                    $job = $this->serializer->deserialize($data->getBody(), JobInterface::class);
                                    if (!$job) {
                                        $this->stdoutLogger->error('Invalid task#');

                                        return Result::DROP;
                                    }
                                    if ($lock->lock($job->getIdentity())) {
                                        try {
                                            $this->process($job);
                                        } catch (\Throwable $e) {
                                            $this->stdoutLogger->error(sprintf(
                                                'Uncaptured exception[%s:%s] detected in %s::%d.',
                                                get_class($e),
                                                $e->getMessage(),
                                                $e->getFile(),
                                                $e->getLine()
                                            ), [
                                                'driver' => get_class($job),
                                            ]);
                                        }
                                        $lock->unlock($job->getIdentity());

                                        return Result::ACK;
                                    }

                                    return Result::DROP;
                                }
                            );
                            break;
                        case 'dag':
                            //TODO
                    }
                }
                );
            }
        }

        return Command::SUCCESS;
    }

    protected function process(JobInterface $job): void
    {
        //TODO execute job
        /**
         * @var Waiter $wait
         */
        $wait = make(Waiter::class);
        try {
            $wait->wait(static fn () => $job->handle(), $job->getTimeout());
        } catch (\Exception $e) {
        }
    }

    protected function bootStrap(): void
    {
        KernelProvider::create(self::COMMADN_PROVIDER_NAME)
            ->bootApp();
    }
}
