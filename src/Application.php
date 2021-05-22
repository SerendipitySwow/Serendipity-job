<?php
declare(strict_types = 1);

namespace Serendipity\Job;

use Dotenv\Dotenv;
use Serendipity\Job\Console\SerendipityJobCommand;
use Swow\Socket;
use Symfony\Component\Console\Application as SymfonyApplication;
use function Serendipity\Job\Kernel\env;

class  Application extends SymfonyApplication
{
    /**
     * @var \Swow\Socket
     */
    protected Socket $server;
    /**
     * @var \Dotenv\Dotenv
     */
    protected Dotenv $dotenv;

    public function __construct(string $name)
    {
        parent::__construct('Serendipity Job Console Tool...');
        $this->addCommands([
            new SerendipityJobCommand()
        ]);
        $this->initialize();
    }

    public function initialize() : void
    {
        // Non-thread-safe load
        $this->dotenv = Dotenv::createUnsafeImmutable(SERENDIPITY_JOB_PATH);
        $this->dotenv->safeLoad();
    }

}
