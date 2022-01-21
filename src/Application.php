<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job;

use Dotenv\Dotenv;
use Hyperf\Di\Container;
use Psr\Container\ContainerInterface;
use Swow\Debug\Debugger;
use SwowCloud\Job\Config\Loader\YamlLoader;
use SwowCloud\Job\Console\ConfigPublishCommand;
use SwowCloud\Job\Console\DagJobCommand;
use SwowCloud\Job\Console\JobCommand;
use SwowCloud\Job\Console\MatrixCommand;
use SwowCloud\Job\Console\SwowCloudJobCommand;
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
        parent::__construct('SwowCloud Job Console Tool...');
        $this->initialize();
        $this->debug();
        $this->addCommands([
            new SwowCloudJobCommand(),
            new JobCommand($container),
            new DagJobCommand($container),
            new MatrixCommand($container),
            new ConfigPublishCommand($container),
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
            Debugger::runOnTTY('swow-cloud-job');
        }
    }
}
