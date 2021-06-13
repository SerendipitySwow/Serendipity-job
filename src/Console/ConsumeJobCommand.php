<?php
declare( strict_types = 1 );


namespace Serendipity\Job\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ConsumeJobCommand extends SymfonyCommand
{
    public static $defaultName = 'scheduler:consume';

    protected function configure (): void
    {
        $this
            ->setDescription('Consumes due tasks')
            ->setDefinition([
                new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the number of tasks consumed'),
                new InputOption('time-limit', 't', InputOption::VALUE_REQUIRED,
                    'Limit the time in seconds the worker can run'),
                new InputOption('failure-limit', 'f', InputOption::VALUE_REQUIRED,
                    'Limit the amount of task allowed to fail'),
                new InputOption('wait', 'w', InputOption::VALUE_NONE, 'Set the worker to wait for tasks every minutes'),
            ])
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> command consumes due tasks.

                        <info>php %command.full_name%</info>

                    Use the --limit option to limit the number of tasks consumed:
                        <info>php %command.full_name% --limit=10</info>

                    Use the --time-limit option to stop the worker when the given time limit (in seconds) is reached:
                        <info>php %command.full_name% --time-limit=3600</info>

                    Use the --failure-limit option to stop the worker when the given amount of failed tasks is reached:
                        <info>php %command.full_name% --failure-limit=5</info>

                    Use the --wait option to set the worker to wait for tasks every minutes:
                        <info>php %command.full_name% --wait</info>
                    EOF
            );
    }

    public function __construct (ConfigInterface $config, StdoutLoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        parent::__construct();
    }


    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

    }

}
