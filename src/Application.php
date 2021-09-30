<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job;

use Dotenv\Dotenv;
use Hyperf\Di\Container;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Config\Loader\YamlLoader;
use Serendipity\Job\Console\DagJobCommand;
use Serendipity\Job\Console\JobCommand;
use Serendipity\Job\Console\SerendipityJobCommand;
use Swow\Debug\Debugger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    protected Dotenv $dotenv;

    /**
     * @var Container
     */
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('Serendipity Job Console Tool...');
        $this->initialize();
        $this->debug();
        $this->addCommands([
            new SerendipityJobCommand(),
            new JobCommand($container),
            new DagJobCommand($container),
        ]);
    }

    public function initialize(): void
    {
        $this->initEnvironment();
        $this->initSingleton();
    }

    protected function initEnvironment(): void
    {
        // Non-thread-safe load
        $this->dotenv = Dotenv::createUnsafeImmutable(BASE_PATH);
        $this->dotenv->safeLoad();
    }

    protected function initSingleton(): void
    {
        $fileLocator = $this->container->make(FileLocator::class, ['paths' => [BASE_PATH . '/config/']]);
        $this->container->set(FileLocatorInterface::class, $fileLocator);
        $this->container->make(YamlLoader::class);
    }

    public function getContainer(): ContainerInterface|Container
    {
        return $this->container;
    }

    protected function debug(): void
    {
        if (env('DEBUG')) {
            Debugger::runOnTTY('serendipity-job');
        }
    }
}
