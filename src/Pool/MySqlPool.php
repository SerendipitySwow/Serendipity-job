<?php

namespace Serendipity\Job\Pool;

use PDO;
use PDOException;
use Serendipity\Job\Contract\LoggerInterface;
use Serendipity\Job\Util\ApplicationContext;
use Swow\Coroutine;
use Serendipity\Job\Pool\Exception\MySqlPoolException;
use function Swow\defer;

class MySqlPool
{
    protected static bool $init = false;
    protected static array $spareConns = [];
    protected static array $busyConns = [];
    protected static array $connsConfig;
    protected static array $connsNameMap = [];
    protected static array $pendingFetchCount = [];
    protected static array $resumeFetchCount = [];

    /**
     * @param  array  $connsConfig
     *
     * @throws MySqlPoolException
     */
    public static function init (array $connsConfig): void
    {
        if (self::$init) {
            return;
        }
        self::$connsConfig = $connsConfig;
        foreach ($connsConfig as $name => $config) {
            self::$spareConns[$name] = [];
            self::$busyConns[$name] = [];
            self::$pendingFetchCount[$name] = [];
            self::$resumeFetchCount[$name] = 0;
            if ($config['maxSpareConns'] <= 0 || $config['maxConns'] <= 0) {
                throw new MySqlPoolException("Invalid maxSpareConns or maxConns in $name");
            }
        }
        self::$init = true;
    }

    /**
     * @param  PDO  $conn
     *
     * @throws MySqlPoolException
     */
    public static function recycle (PDO $conn): void
    {
        if (!self::$init) {
            throw new MySqlPoolException('Should call MySQLPool::init.');
        }
        $id = spl_object_hash($conn);
        $connName = self::$connsNameMap[$id];
        if (isset(self::$busyConns[$connName][$id])) {
            unset(self::$busyConns[$connName][$id]);
        } else {
            throw new MySqlPoolException('Unknow MySQL connection.');
        }
        $connsPool = &self::$spareConns[$connName];
        if ($conn->getAttribute(PDO::ATTR_SERVER_INFO)) {
            if (count($connsPool) >= self::$connsConfig[$connName]['maxSpareConns']) {
                unset($conn);
            } else {
                $connsPool[] = $conn;
                if (count(self::$pendingFetchCount[$connName]) > 0) {
                    self::$resumeFetchCount[$connName]++;
                    Coroutine::resume(array_shift(self::$pendingFetchCount[$connName]));
                }
                return;
            }
        }
        unset(self::$connsNameMap[$id]);
    }

    /**
     * @param $connName
     *
     * @return \PDO|bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     * @throws \Serendipity\Job\Pool\Exception\MySqlPoolException
     */
    public static function fetch ($connName): PDO|bool
    {
        if (!self::$init) {
            throw new MySqlPoolException('Should call MySQLPool::init!');
        }
        if (!isset(self::$connsConfig[$connName])) {
            throw new MySqlPoolException("Invalid connName: $connName.");
        }
        $connsPool = &self::$spareConns[$connName];
        if (!empty($connsPool) && count($connsPool) > self::$resumeFetchCount[$connName]) {
            /**
             * @var Pdo $conn
             */
            $conn = array_pop($connsPool);
            if (!$conn->getAttribute(PDO::ATTR_SERVER_INFO)) {
                $conn = self::reconnect($conn, $connName);
            } else {
                $id = spl_object_hash($conn);
                self::$busyConns[$connName][$id] = $conn;
            }
            defer(function () use ($conn) {
                self::recycle($conn);
            });
            return $conn;
        }
        if (count(self::$busyConns[$connName]) + count($connsPool) === self::$connsConfig[$connName]['maxConns']) {
            $cid = Coroutine::getCurrent()
                            ->getId();
            self::$pendingFetchCount[$connName][$cid] = $cid;
            if (Coroutine::yield($cid) === false) {
                unset(self::$pendingFetchCount[$connName][$cid]);
                throw new MySqlPoolException('Reach max connections! Conn\'t pending fetch!');
            }
            self::$resumeFetchCount[$connName]--;
            if (!empty($connsPool)) {
                $conn = array_pop($connsPool);
                if (!$conn->getAttribute(PDO::ATTR_SERVER_INFO)) {
                    $conn = self::reconnect($conn, $connName);
                } else {
                    self::$busyConns[$connName][spl_object_hash($conn)] = $conn;
                }
                defer(function () use ($conn) {
                    self::recycle($conn);
                });
                return $conn;
            }

            return false;//should not happen
        }
        $conn = static::getConnection($connName);
        $id = spl_object_hash($conn);
        self::$connsNameMap[$id] = $connName;
        self::$busyConns[$connName][$id] = $conn;

        if (!$conn instanceof PDO) {
            unset(self::$busyConns[$connName][$id], self::$connsNameMap[$id]);
            throw new MySqlPoolException('Conn\'t connect to MySQL server: ' . json_encode(self::$connsConfig[$connName],
                    JSON_THROW_ON_ERROR));
        }
        defer(function () use ($conn) {
            self::recycle($conn);
        });
        return $conn;
    }

    /**
     * 断线重链
     *
     * @param  \PDO  $conn
     * @param  string  $connName
     *
     * @return \PDO
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     * @throws \Serendipity\Job\Pool\Exception\MySqlPoolException
     */
    public static function reconnect (PDO $conn, string $connName): PDO
    {
        if (!$conn->getAttribute(PDO::ATTR_SERVER_INFO)) {
            $old_id = spl_object_hash($conn);
            unset(self::$busyConns[$connName][$old_id], self::$connsNameMap[$old_id]);
            $conn = static::getConnection($connName);
            if (!$conn instanceof PDO) {
                throw new MySqlPoolException('Conn\'t connect to MySQL server: ' . json_encode(self::$connsConfig[$connName],
                        JSON_THROW_ON_ERROR));
            }
            $id = spl_object_hash($conn);
            self::$connsNameMap[$id] = $connName;
            self::$busyConns[$connName][$id] = $conn;
            return $conn;
        }
        return $conn;
    }

    /**
     * @param  string  $connName
     *
     * @return ?PDO
     */
    protected static function getConnection (string $connName): ?PDO
    {
        $config = self::$connsConfig[$connName];
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s,charset=%s', $config['host'], $config['port'],
            $config['dbname'], $config['charset']);
        try {
            return new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            ApplicationContext::getContainer()
                              ->get(LoggerInterface::class)
                              ->error($e->getMessage());
        }
        return null;
    }
}
