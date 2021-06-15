<?php
/**
 * This file is part of Serendipity Job
 *
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare( strict_types = 1 );

namespace Serendipity\Job\Console;

use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\SerializerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Provider\KernelProvider;
use Serendipity\Job\Nsq\Consumer\AbstractConsumer;
use Serendipity\Job\Nsq\Consumer\DagConsumer;
use Serendipity\Job\Nsq\Consumer\TaskConsumer;
use Serendipity\Job\Serializer\SymfonySerializer;
use Serendipity\Job\Util\Waiter;
use SerendipitySwow\Nsq\Nsq;
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

    protected ?Nsq $subscriber = null;

    protected function configure (): void
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

    public function __construct (ContainerInterface $container)
    {
        $this->container = $container;
        $this->bootStrap();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        $this->serializer = $this->container
            ->get(SymfonySerializer::class);
        $this->subscriber = make(Nsq::class, [
            $container,
            $this->config->get('nsq.default')
        ]);
        $this->waiter = make(Waiter::class, [ 0 ]);
        parent::__construct();
    }

    public function handle (): int
    {
        $this->stdoutLogger->info('Consumer Task Successfully Processed#');
        $limit = $this->input->getOption('limit');
        $type = $this->input->getOption('type');
        if (!in_array($type, self::TASK_TYPE, true)) {
            $this->stdoutLogger->error('Invalid task parameters.');
            exit();
        }
        if ($limit !== null) {
            $config = $this->config
                ->get('nsq.default');
            $this->waiter = make(Waiter::class, [ 0 ]);
            for ($i = 0; $i < $limit; $i++) {
                Coroutine::run(
                    function () use ($config, $i, $type) {
                        $consumer = match ( $type ) {
                            'task' => $this->makeConsumer(TaskConsumer::class, self::TOPIC_PREFIX . $type, 'Consumerd'),
                            'dag' => $this->makeConsumer(DagConsumer::class, self::TOPIC_PREFIX . $type, 'Consumerd')
                        };
                        try {

                        } catch (Throwable $e) {

                        }
                    }
                );
            }
        }

        return Command::SUCCESS;
    }


    /**
     * @param  string  $class
     * @param  string  $topic
     * @param  string  $channel
     *
     * @return AbstractConsumer
     */
    protected function makeConsumer (string $class, string $topic, string $channel): AbstractConsumer
    {
        /**
         * @var AbstractConsumer $consumer
         */
        $consumer = make($class);
        $consumer->setTopic($topic);
        $consumer->setChannel($channel);
        return $consumer;
    }

    protected function bootStrap (): void
    {
        KernelProvider::create(self::COMMADN_PROVIDER_NAME)
                      ->bootApp();
    }
}
