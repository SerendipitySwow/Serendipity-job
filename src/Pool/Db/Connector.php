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

use Exception;
use Hyperf\Utils\Str;
use PDO;
use Throwable;

class Connector
{
    /**
     * The default PDO connection options.
     */
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Create a new PDO connection.
     *
     * @throws Exception|Throwable
     */
    public function createConnection(string $dsn, array $config, array $options): PDO
    {
        [$username, $password] = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];

        try {
            return $this->createPdoConnection(
                $dsn,
                $username,
                $password,
                $options
            );
        } catch (Exception $e) {
            return $this->tryAgainIfCausedByLostConnection(
                $e,
                $dsn,
                $username,
                $password,
                $options
            );
        }
    }

    /**
     * Get the PDO options based on the configuration.
     */
    public function getOptions(array $config): array
    {
        return array_replace($this->options, $config['options'] ?? []);
    }

    /**
     * Get the default PDO connection options.
     */
    public function getDefaultOptions(): array
    {
        return $this->options;
    }

    /**
     * Set the default PDO connection options.
     */
    public function setDefaultOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Create a new PDO connection instance.
     */
    protected function createPdoConnection(string $dsn, string $username, string $password, array $options): PDO
    {
        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Determine if the connection is persistent.
     */
    protected function isPersistentConnection(array $options): bool
    {
        return isset($options[PDO::ATTR_PERSISTENT]) &&
            $options[PDO::ATTR_PERSISTENT];
    }

    /**
     * Handle an exception that occurred during connect execution.
     *
     * @throws Throwable
     */
    protected function tryAgainIfCausedByLostConnection(Throwable $e, string $dsn, string $username, string $password, array $options): ?PDO
    {
        if ($this->causedByLostConnection($e)) {
            return $this->createPdoConnection($dsn, $username, $password, $options);
        }

        throw $e;
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     */
    protected function causedByLostConnection(Throwable $e): bool
    {
        $message = $e->getMessage();

        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'Name or service not known',
            'ORA-03114',
            'Packets out of order. Expected',
        ]);
    }
}
