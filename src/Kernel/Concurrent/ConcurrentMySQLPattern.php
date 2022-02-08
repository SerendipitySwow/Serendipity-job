<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Concurrent;

use Hyperf\Engine\Channel;
use Hyperf\Utils\Coroutine as HyperfCo;
use PDO;
use Psr\Log\LoggerInterface;
use SwowCloud\Job\Kernel\Concurrent\Exception\MySQLRuntimeException;
use SwowCloud\Job\Util\Coordinator\Constants;
use SwowCloud\Job\Util\Coordinator\CoordinatorManager;
use Throwable;

class ConcurrentMySQLPattern
{
    /**
     * @var ?PDO
     */
    protected ?PDO $PDO;

    /**
     * @var ?Channel
     */
    protected ?Channel $chan;

    protected bool $transaction = false;

    protected LoggerInterface $logger;

    public function __construct(PDO $PDO, LoggerInterface $logger)
    {
        $this->PDO = $PDO;
        $this->logger = $logger;
    }

    public function isTransaction(): bool
    {
        return $this->PDO->inTransaction();
    }

    public function isOpen(): bool
    {
        return $this->PDO !== null;
    }

    public function loop(): void
    {
        $this->chan = new Channel(1);
        HyperfCo::create(function () {
            while (true) {
                try {
                    $closure = $this->chan->pop();
                    if (!$closure) {
                        break;
                    }
                    $closure->call($this);
                } catch (Throwable $e) {
                    $this->logger->error('Pdo error:' . $e->getMessage());
                    $this->PDO = null;
                    break;
                }
            }
        });

        static $once;
        if (!isset($once)) {
            $once = true;
            HyperfCo::create(function () {
                CoordinatorManager::until(Constants::COMMAND_EXIT)->yield();
                $this->chan?->close();
            });
        }
    }

    /**
     * Open the transaction.
     */
    public function beginTransaction(): bool
    {
        if (!$this->chan) {
            $this->loop();
        }
        $this->transaction = true;

        return $this->PDO->beginTransaction();
    }

    /**
     * Transaction commit.
     */
    public function commit(): bool
    {
        if (!$this->chan) {
            $this->loop();
        }
        if ($this->PDO->inTransaction()) {
            return $this->PDO->commit();
        }
        throw new MySQLRuntimeException('PDO does not open a transaction#.');
    }

    /**
     * Transaction rollback.
     */
    public function rollback(): bool
    {
        if (!$this->chan) {
            $this->loop();
        }
        if ($this->PDO->inTransaction()) {
            return $this->PDO->rollBack();
        }
        throw new MySQLRuntimeException('PDO does not open a transaction#.');
    }

    /**
     * Close the mysql.
     */
    public function close(): void
    {
        if (!Coroutine::inCoroutine()) {
            $this->PDO = null;

            return;
        }

        if (!$this->chan) {
            $this->loop();
        }
        if ($this->getPDO()->inTransaction()) {
            $this->logger->error('Maybe you\'ve forgotten to commit or rollback the MySQL transaction.');
        }
        $this->chan->push(function () {
            $this->PDO = null;
        });
    }

    public function getPDO(): PDO
    {
        return $this->PDO;
    }
}
