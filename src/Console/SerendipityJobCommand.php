<?php
declare( strict_types = 1 );

namespace Serendipity\Job\Console;

use Serendipity\Job\Kernel\Provider\KernelProvider;
use Swow\Debug\Debugger;
use Serendipity\Job\Constant\Logo;
use Swow\Coroutine;
use function Serendipity\Job\Kernel\serendipity_env;

class SerendipityJobCommand extends Command
{
    protected static string $defaultName = 'serendipity-job:start';

    protected function configure (): void
    {
        $this->setDescription('Start Serendipity Job')
             ->setHelp('This command allows you start Serendipity Job...');
    }

    public function handle (): int
    {
        if (serendipity_env('DEBUG') === true) {
            Debugger::runOnTTY('serendipity-job');
        }
        $this->output->writeln(sprintf('<info>%s</info>', Logo::LOGO));
        $this->output->writeln([
            '<info>Serendipity Job</info>',
            '<info>===============</info>',
            ''
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

    protected function bootStrap (): void
    {
        KernelProvider::create()
                      ->bootApp();
    }
}
