<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis\Pool;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Redis\Frequency;
use Serendipity\Job\Redis\RedisConnection;
use Serendipity\Job\Util\Arr;

class RedisPool extends Pool
{
    protected string $name;

    protected array $config;

    public function __construct(ContainerInterface $container, string $name)
    {
        $this->name = $name;
        $config = $container->get(ConfigInterface::class);
        $key = sprintf('redis.%s', $this->name);
        if (!$config->has($key)) {
            throw new \InvalidArgumentException(sprintf('config[%s] is not exist!', $key));
        }

        $this->config = $config->get($key);
        $options = Arr::get($this->config, 'pool', []);

        $this->frequency = make(Frequency::class);

        parent::__construct($container, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function createConnection(): ConnectionInterface
    {
        return new RedisConnection($this->container, $this, $this->config);
    }
}
