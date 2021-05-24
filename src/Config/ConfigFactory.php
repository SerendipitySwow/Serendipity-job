<?php

declare(strict_types = 1);

namespace Serendipity\Job\Config;

use Symfony\Component\Finder\Finder;

class ConfigFactory
{

    public function __call(string $name, array $arguments)
    {
        return $this->instance->$name(...$arguments);
    }

    public function __construct()
    {
        $this->instance = $this->initialize();
    }

    private function initialize() : Config
    {
        $configPath     = SERENDIPITY_JOB_PATH . '/config/';
        $config         = $this->readConfig($configPath . 'config.php');
        $autoloadConfig = $this->readPaths([SERENDIPITY_JOB_PATH . '/config/autoload']);
        $merged         = array_merge_recursive(ProviderConfig::load(), $config, ...$autoloadConfig);
        return new Config($merged);
    }

    private function readConfig(string $configPath) : array
    {
        $config = [];
        if (file_exists($configPath) && is_readable($configPath)) {
            $config = require $configPath;
        }
        return is_array($config) ? $config : [];
    }

    private function readPaths(array $paths) : array
    {
        $configs = [];
        $finder  = new Finder();
        $finder->files()->in($paths)->name('*.php');
        foreach ($finder as $file) {
            $configs[] = [
                $file->getBasename('.php') => require $file->getRealPath(),
            ];
        }
        return $configs;
    }
}
