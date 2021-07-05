<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Serendipity\Job\Db\Pool;

use Hyperf\Pool\Pool as HyperfPool;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Db\Frequency;

abstract class Pool extends HyperfPool
{
    protected string $name;

    protected array $config;

    public function __construct(ContainerInterface $container, string $name)
    {
        $config = $container->get(ConfigInterface::class);
        $key = sprintf('db.%s', $name);
        if (!$config->has($key)) {
            throw new \InvalidArgumentException(sprintf('config[%s] is not exist!', $key));
        }

        $this->name = $name;
        $this->config = $config->get($key);
        $options = Arr::get($this->config, 'pool', []);
        $this->frequency = make(Frequency::class, [$this]);

        parent::__construct($container, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
