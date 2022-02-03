<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Config;

use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;

class ConfigFactory
{
    public function __invoke(ContainerInterface $container): Config
    {
        $configPath = BASE_PATH . '/config/';
        $config = $this->readConfig($configPath . 'config.php');
        $autoloadConfig = $this->readPaths([BASE_PATH . '/config/autoload']);
        $merged = array_merge_recursive(['providers' => ProviderConfig::load()], $config, ...$autoloadConfig);

        return new Config($merged);
    }

    /**
     * @return array<string,string[]>
     */
    private function readConfig(string $configPath): array
    {
        $config = [];
        if (file_exists($configPath) && is_readable($configPath)) {
            $config = require $configPath;
        }

        return is_array($config) ? $config : [];
    }

    /**
     * @param array<string> $paths
     *
     * @return array<int, string[]>
     */
    private function readPaths(array $paths): array
    {
        $configs = [];
        $finder = new Finder();
        $finder->files()
            ->in($paths)
            ->name('*.php');
        foreach ($finder as $file) {
            $configs[] = [
                $file->getBasename('.php') => require $file->getRealPath(),
            ];
        }

        return $configs;
    }
}
