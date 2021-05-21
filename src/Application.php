<?php
declare(strict_types = 1);

namespace Serendipity\Job;

use Serendipity\Job\Console\SerendipityJobCommand;
use Swow\Socket;
use \Symfony\Component\Console\Application as SymfonyApplication;

class  Application extends SymfonyApplication
{
    /**
     * @var \Swow\Socket
     */
    protected Socket $server;

    public function __construct()
    {
        parent::__construct('Serendipity Job Console Tool...');
        $this->addCommands([
            new SerendipityJobCommand()
        ]);
    }
}
