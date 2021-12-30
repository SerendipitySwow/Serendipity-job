<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Console;

use Hyperf\Utils\ApplicationContext;
use Swow\Coroutine;
use SwowCloud\Contract\StdoutLoggerInterface;
use SwowCloud\Job\Kernel\Provider\KernelProvider;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * @command php bin/job swow-cloud-job:start
 */
final class SwowCloudJobCommand extends Command
{
    protected static $defaultName = 'swow-cloud-job:start';

    protected const COMMAND_PROVIDER_NAME = 'SwowCloud-Job';

    protected function configure(): void
    {
        $this->setDescription('Start SwowCloud Job')
            ->setHelp('This command allows you start SwowCloud Job...');
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
