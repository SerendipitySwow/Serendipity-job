<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Concurrent;

use Hyperf\Engine\Channel;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use PDO;
use Psr\Log\LoggerInterface;
use Serendipity\job\Kernel\Concurrent\Exception\MySQLRuntimeException;
use Serendipity\Job\Util\Coroutine;
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
        Coroutine::create(function () {
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
            Coroutine::create(function () {
                ## TODO 协程监听
                /*
                CoordinatorManager::until(Constants::WORKER_EXIT)->yield();
                */
                if ($this->chan) {
                    $this->chan->close();
                }
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

        $this->chan->push(function () {
            $this->PDO = null;
        });
    }

    public function getPDO(): PDO
    {
        return $this->PDO;
    }
}
