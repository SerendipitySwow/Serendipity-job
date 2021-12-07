<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Console;

use Hyperf\Utils\ApplicationContext;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Kernel\Provider\KernelProvider;
use Swow\Coroutine;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * @command php bin/serendipity-job serendipity-job:start
 */
final class SerendipityJobCommand extends Command
{
    protected static $defaultName = 'serendipity-job:start';

    protected const COMMAND_PROVIDER_NAME = 'Serendipity-Job';

    protected function configure(): void
    {
        $this->setDescription('Start Serendipity Job')
            ->setHelp('This command allows you start Serendipity Job...');
    }

    public function handle(): int
    {
        $this->showLogo();

        $this->bootStrap();

        return SymfonyCommand::SUCCESS;
    }

    protected function checkProcess(string $cwd): void
    {
        if (file_exists("{$cwd}/.pid.server")) {
            ApplicationContext::getContainer()->get(StdoutLoggerInterface::class)->error('An already started Job development server has been found.');
            Coroutine::killAll();
            exit(255);
        }
    }

    protected function bootStrap(): void
    {
        KernelProvider::create(self::COMMAND_PROVIDER_NAME)
            ->bootApp();
    }
}
