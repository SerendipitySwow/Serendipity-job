<?php
declare(strict_types = 1);

namespace Serendipity\Job\Console;

class SerendipityJobCommand extends Command
{
    protected static string $defaultName = 'serendipity-job:start';

    protected function configure() : void
    {
        $this->setDescription('Start Serendipity Job')
             ->setHelp('This command allows you start Serendipity Job...');
    }

    public function handle() : void
    {
        // TODO: Implement handle() method.
    }
}
