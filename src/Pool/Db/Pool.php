<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Pool\Db;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool as AbstractPool;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Util\Arr;

class Pool extends AbstractPool
{
    protected string $name;

    protected $config;

    public function __construct(ContainerInterface $container, string $name)
    {
        $this->name = $name;
        $config = $container->get(ConfigInterface::class);
        $key = sprintf('databases.%s', $this->name);
        if (!$config->has($key)) {
            throw new \InvalidArgumentException(sprintf('config[%s] is not exist!', $key));
        }
        // Rewrite the `name` of the configuration item to ensure that the model query builder gets the right connection.
        $config->set("{$key}.name", $name);

        $this->config = $config->get($key);
        $options = Arr::get($this->config, 'pool', []);

        $this->frequency = make(Frequency::class, [$this]);
        parent::__construct($container, $options);
    }

    protected function createConnection(): ConnectionInterface
    {
        return new Connection($this->container, $this, $this->config);
    }
}
