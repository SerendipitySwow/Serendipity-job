<?php
declare( strict_types = 1 );

namespace Serendipity\Job\Config\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlLoader extends FileLoader
{

    public function load ($resource, string $type = null)
    {
        if ($this->supports($resource, $type)) {
            return Yaml::parse(file_get_contents($resource));
        }
        return [];
    }

    public function supports ($resource, string $type = null): bool
    {
        return is_string($resource) && 'yaml' === pathinfo(
                $resource,
                PATHINFO_EXTENSION
            );
    }
}
