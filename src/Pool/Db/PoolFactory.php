<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Pool\Db;

use Hyperf\Di\Container;
use Psr\Container\ContainerInterface;

class PoolFactory
{
    /**
     * @var Container|ContainerInterface
     */
    protected ContainerInterface | Container $container;

    /**
     * @var Pool[]
     */
    protected array $pools = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getPool(string $name): Pool
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }

        if ($this->container instanceof Container) {
            $pool = $this->container->make(Pool::class, ['name' => $name]);
        } else {
            $pool = new Pool($this->container, $name);
        }

        return $this->pools[$name] = $pool;
    }
}
