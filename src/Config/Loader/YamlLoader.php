<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Config\Loader;

use RuntimeException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlLoader extends FileLoader
{
    public function load($resource, string $type = null): mixed
    {
        if ($this->supports($resource, $type)) {
            $contents = file_get_contents($resource);
            if ($contents === false) {
                throw new RuntimeException(sprintf('Error reading file:%s', $resource));
            }

            return Yaml::parse($contents);
        }

        return [];
    }

    public function supports($resource, string $type = null): bool
    {
        return is_string($resource) && pathinfo(
            $resource,
            PATHINFO_EXTENSION
        ) === 'yaml';
    }
}
