<?php
declare(strict_types = 1);

namespace Serendipity\Job\Console;

use Serendipity\Job\Kernel\Provider\KernelProvider;
use Swow\Debug\Debugger;
use Serendipity\Job\Constant\Logo;
use function Serendipity\Job\Kernel\env;

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
        if (env('DEBUG') === true) {
            Debugger::runOnTTY();
        }
        $this->output->writeln(sprintf('<info>%s</info>', Logo::LOGO));
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
