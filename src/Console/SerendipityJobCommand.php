<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Console;

use Serendipity\Job\Constant\Logo;
use Serendipity\Job\Kernel\Provider\KernelProvider;
use Swow\Coroutine;

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
        $this->output->writeln(sprintf('<info>%s</info>', Logo::LOGO));
        $this->output->writeln([
            '<info>Serendipity Job</info>',
            '<info>===============</info>',
            '',
        ]);
        $this->output->writeln([
            '<comment>If You Want To Exit, You Can Press Ctrl + C To Exit#.<comment>',
            '<info>===============</info>',
        ]);

        Coroutine::run(function () {
            $this->bootStrap();
        });

        return Command::SUCCESS;
    }

    protected function bootStrap(): void
    {
        KernelProvider::create(self::COMMAND_PROVIDER_NAME)
            ->bootApp();
    }
}
