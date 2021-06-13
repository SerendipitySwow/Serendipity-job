<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/SerendipityJob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Console;

use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Lock\RedisLock;
use Serendipity\Job\Kernel\Provider\KernelProvider;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Nsq;
use SerendipitySwow\Nsq\Result;
use Swow\Coroutine;
use Symfony\Component\Console\Input\InputOption;

final class ConsumeJobCommand extends Command
{
    public static $defaultName = 'scheduler:consume';

    protected const COMMADN_PROVIDER_NAME = 'Consumer-Job';

    public const TOPIC = 'serendipity-job';

    protected function configure(): void
    {
        $this
            ->setDescription('Consumes tasks')
            ->setDefinition([
                new InputOption(
                    'limit',
                    'l',
                    InputOption::VALUE_REQUIRED,
                    'Configure the number of coroutines to process tasks'
                ),
            ])
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> command consumes tasks

                        <info>php %command.full_name%</info>

                    Use the --limit option configure the number of coroutines to process tasks:
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
        $this->stdoutLogger->debug('Consumer Task Successfully Processed#');
        $limit = $this->input->getOption('limit');

        if ($limit !== null) {
            $config = $this->config
                ->get('nsq.default');
            $coIds = [];
            for ($i = 0; $i < $limit; $i++) {
                $coIds[] = Coroutine::run(function () use ($config, $i) {
                    /**
                     * @var Nsq $nsq
                     */
                    $nsq = make(Nsq::class, [$this->container, $config]);
                    $redis = ( new \Redis() );
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

                    $nsq->subscribe(self::TOPIC, sprintf('Consumerd%s', $i), function (Message $data) use ($lock) {
                        $this->stdoutLogger->info('Subscribe ' . $data->getBody() . PHP_EOL);
                        if ($lock->lock($data->getBody())) {
                            //TODO Task Handle
                            $lock->unlock($data->getBody());
                            $this->stdoutLogger->warning('Unsubscribe ' . $data->getBody() . PHP_EOL);
                            return Result::ACK;
                        }
                        $this->stdoutLogger->error('Unsubscribe ' . $data->getBody() . PHP_EOL);

                        return Result::DROP;
                    });
                });
            }
        }

        return Command::SUCCESS;
    }

    protected function bootStrap(): void
    {
        KernelProvider::create(self::COMMADN_PROVIDER_NAME)
            ->bootApp();
    }
}
