<?php
declare(strict_types = 1);

namespace Serendipity\Job\Console;

use Serendipity\Job\Config\ConfigProvider;
use Serendipity\Job\Config\ProviderConfig;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Util\ApplicationContext;

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
        ProviderConfig::load();

        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        dd($config);

        return Command::SUCCESS;
    }
}
