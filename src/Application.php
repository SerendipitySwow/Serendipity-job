<?php
declare(strict_types = 1);

namespace Serendipity\Job;

use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Config\ProviderConfig;
use Serendipity\Job\Console\SerendipityJobCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class  Application extends SymfonyApplication
{
    /**
     * @var \Dotenv\Dotenv
     */
    protected Dotenv $dotenv;

    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected ContainerInterface $container;

    public function __construct()
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
