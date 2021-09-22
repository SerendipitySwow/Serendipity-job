<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis;

use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;

/**
 * @method bool select(int $db)
 */
class RedisConnection extends BaseConnection
{
    use ScanCaller;

    protected \Redis $connection;

    protected array $config = [
        'host' => 'localhost',
        'port' => 6379,
        'auth' => null,
        'db' => 0,
        'timeout' => 0.0,
        'cluster' => [
            'enable' => false,
            'name' => null,
            'seeds' => [],
            'read_timeout' => 0.0,
            'persistent' => false,
        ],
        'sentinel' => [
            'enable' => false,
            'master_name' => '',
            'nodes' => [],
            'persistent' => '',
            'read_timeout' => 0,
        ],
        'options' => [],
    ];

    /**
     * Current redis database.
     */
    protected ?int $database = null;

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = array_replace_recursive($this->config, $config);

        $this->reconnect();
    }

    public function __call($name, $arguments)
    {
        try {
            $result = $this->connection->{$name}(...$arguments);
        } catch (\Throwable $exception) {
            $result = $this->retry($name, $arguments, $exception);
        }

        return $result;
    }

    public function getActiveConnection(): static
    {
        if ($this->check()) {
            return $this;
        }

        if (!$this->reconnect()) {
            throw new ConnectionException('Connection reconnect failed.');
        }

        return $this;
    }

    public function reconnect(): bool
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $auth = $this->config['auth'];
        $db = $this->config['db'];
        $timeout = $this->config['timeout'];
        $cluster = $this->config['cluster']['enable'] ?? false;
        $sentinel = $this->config['sentinel']['enable'] ?? false;

        $redis = match (true) {
            $cluster => $this->createRedisCluster(),
            $sentinel => $this->createRedisSentinel(),
            default => $this->createRedis($host, $port, $timeout),
        };

        $options = $this->config['options'] ?? [];

        foreach ($options as $name => $value) {
            // The name is int, value is string.
            $redis->setOption($name, $value);
        }

        if ($redis instanceof \Redis && isset($auth) && $auth !== '') {
            $redis->auth($auth);
        }

        $database = $this->database ?? $db;
        if ($database > 0) {
            $redis->select($database);
        }

        $this->connection = $redis;
        $this->lastUseTime = microtime(true);

        return true;
    }

    public function close(): bool
    {
        unset($this->connection);

        return true;
    }

    public function release(): void
    {
        if ($this->database && $this->database !== $this->config['db']) {
            // Select the origin db after execute select.
            $this->select($this->config['db']);
            $this->database = null;
        }
        parent::release();
    }

    public function setDatabase(?int $database): void
    {
        $this->database = $database;
    }

    protected function createRedisCluster(): \RedisCluster
    {
        try {
            $paramaters = [];
            $paramaters[] = $this->config['cluster']['name'] ?? null;
            $paramaters[] = $this->config['cluster']['seeds'] ?? [];
            $paramaters[] = $this->config['timeout'] ?? 0.0;
            $paramaters[] = $this->config['cluster']['read_timeout'] ?? 0.0;
            $paramaters[] = $this->config['cluster']['persistent'] ?? false;
            if (isset($this->config['auth'])) {
                $paramaters[] = $this->config['auth'];
            }

            $redis = new \RedisCluster(...$paramaters);
        } catch (\Throwable $e) {
            throw new ConnectionException('Connection reconnect failed ' . $e->getMessage());
        }

        return $redis;
    }

    protected function retry($name, $arguments, \Throwable $exception)
    {
        $logger = $this->container->get(StdoutLoggerInterface::class);
        $logger->warning('Redis::__call failed, because ' . $exception->getMessage());

        try {
            $this->reconnect();
            $result = $this->connection->{$name}(...$arguments);
        } catch (\Throwable $exception) {
            $this->lastUseTime = 0.0;
            throw $exception;
        }

        return $result;
    }

    protected function createRedisSentinel(): \Redis
    {
        try {
            $nodes = $this->config['sentinel']['nodes'] ?? [];
            $timeout = $this->config['timeout'] ?? 0;
            $persistent = $this->config['sentinel']['persistent'] ?? null;
            $retryInterval = $this->config['retry_interval'] ?? 0;
            $readTimeout = $this->config['sentinel']['read_timeout'] ?? 0;
            $masterName = $this->config['sentinel']['master_name'] ?? '';

            $host = '';
            $port = 0;
            foreach ($nodes as $node) {
                [$sentinelHost, $sentinelPort] = explode(':', $node);
                $sentinel = new \RedisSentinel(
                    $sentinelHost,
                    (int) $sentinelPort,
                    $timeout,
                    $persistent,
                    $retryInterval,
                    $readTimeout
                );
                $masterInfo = $sentinel->getMasterAddrByName($masterName);
                if (is_array($masterInfo) && count($masterInfo) >= 2) {
                    [$host, $port] = $masterInfo;
                    break;
                }
            }
            $redis = $this->createRedis($host, $port, $timeout);
        } catch (\Throwable $e) {
            throw new ConnectionException('Connection reconnect failed ' . $e->getMessage());
        }

        return $redis;
    }

    /**
     * @throws ConnectionException
     */
    protected function createRedis(string $host, int $port, float $timeout): \Redis
    {
        $redis = new \Redis();
        if (!$redis->connect($host, $port, $timeout)) {
            throw new ConnectionException('Connection reconnect failed.');
        }

        return $redis;
    }
}
