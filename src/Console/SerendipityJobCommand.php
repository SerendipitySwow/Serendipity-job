<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Console;

use Serendipity\Job\Kernel\Provider\KernelProvider;
use Swow\Coroutine;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

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

        Coroutine::run(function () {
            $this->bootStrap();
        });

        return SymfonyCommand::SUCCESS;
    }

    protected function bootStrap(): void
    {
        KernelProvider::create(self::COMMAND_PROVIDER_NAME)
            ->bootApp();
    }
}
