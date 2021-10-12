<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Console;

use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\SerializerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Provider\KernelProvider;
use Serendipity\Job\Nsq\Consumer\AbstractConsumer;
use Serendipity\Job\Nsq\Consumer\DagConsumer;
use Serendipity\Job\Util\Coroutine as SerendipitySwowCo;
use SerendipitySwow\Nsq\Message;
use SerendipitySwow\Nsq\Nsq;
use SerendipitySwow\Nsq\Result;
use Spatie\Emoji\Emoji;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * @command php bin/serendipity-job dag:start
 */
final class DagJobCommand extends Command
{
    public static $defaultName = 'dag:start';

    protected const COMMAND_PROVIDER_NAME = 'Dag';

    public const TOPIC_SUFFIX = 'dag';

    protected ?ConfigInterface $config = null;

    protected ?StdoutLoggerInterface $stdoutLogger = null;

    protected ?SerializerInterface $serializer = null;

    protected ?Nsq $subscriber = null;

    protected function configure(): void
    {
        $this
            ->setDescription('Start Dag Job.');
    }

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    public function handle(): int
    {
        $this->config = $this->container->get(ConfigInterface::class);
        $this->stdoutLogger = $this->container->get(StdoutLoggerInterface::class);
        $this->bootStrap();
        $this->stdoutLogger->info(str_repeat(Emoji::flagsForFlagChina() . '  ', 10));
        $this->stdoutLogger->info(sprintf('%s DagConsumer Successfully Processed# %s', Emoji::manSurfing(), Emoji::rocket()));
        $this->subscribe();

        return SymfonyCommand::SUCCESS;
    }

    protected function subscribe(): void
    {
        SerendipitySwowCo::create(
            function () {
                $subscriber = make(Nsq::class, [
                    $this->container,
                    $this->config->get(sprintf('nsq.%s', 'default')),
                ]);
                $consumer = $this->makeConsumer(DagConsumer::class, AbstractConsumer::TOPIC_PREFIX . self::TOPIC_SUFFIX, 'DagConsumer');
                $subscriber->subscribe(
                    AbstractConsumer::TOPIC_PREFIX . self::TOPIC_SUFFIX,
                    'DagConsumer',
                    function (Message $message) use ($consumer) {
                        try {
                            $result = $consumer->consume($message);
                        } catch (Throwable $error) {
                            //Segmentation fault
                            $this->stdoutLogger->error(
                                sprintf(
                                    'Consumer failed to consume %s,reason: %s,file: %s,line: %s',
                                    'Consumer',
                                    $error->getMessage(),
                                    $error->getFile(),
                                    $error->getLine()
                                )
                            );
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
        $this->showLogo();
        KernelProvider::create(self::COMMAND_PROVIDER_NAME)
            ->bootApp();
    }
}
