<?php
declare(strict_types = 1);

namespace Serendipity\Job\Console;

use Serendipity\Job\Kernel\Provider\KernelProvider;
use Serendipity\Job\Util\ApplicationContext;
use function Serendipity\Job\Kernel\config;

class SerendipityJobCommand extends Command
{
    protected static string $defaultName = 'serendipity-job:start';

    protected function configure() : void
    {
        $this->setDescription('Start Serendipity Job')
             ->setHelp('This command allows you start Serendipity Job...');
    }

    public function handle() : int
    {
        $this->output->writeln([
            '<info>Serendipity Job</info>',
            '<info>===============</info>',
            ''
        ]);
        $this->bootStrap();
        return Command::SUCCESS;
    }

    protected function bootStrap() : void
    {
        KernelProvider::create()->bootApp();
    }
}
