<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
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

namespace Serendipity\Job\Pool\Db;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use PDO;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Pool\Db\Pool as DbPool;

class Connection extends BaseConnection
{
    /**
     * @var DbPool
     */
    protected $pool;

    protected ?PDO $connection = null;

    protected array $config;

    protected ?StdoutLoggerInterface $logger = null;

    protected bool $transaction = false;

    public function __construct(ContainerInterface $container, DbPool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = $config;
        $this->logger = $container->get(StdoutLoggerInterface::class);

        $this->reconnect();
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public function getActiveConnection(): ConnectionInterface
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
        $this->close();
        $connector = new MySqlConnector();
        $this->connection = $connector->connect($this->config);

        $this->lastUseTime = microtime(true);

        return true;
    }

    public function close(): bool
    {
        $this->connection = null;
        unset($this->connection);

        return true;
    }

    public function release(): void
    {
        if ($this->isTransaction()) {
            $this->rollBack(0);
            $this->logger->error('Maybe you\'ve forgotten to commit or rollback the MySQL transaction.');
        }

        parent::release();
    }

    public function setTransaction(bool $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function isTransaction(): bool
    {
        return $this->transaction;
    }
}
