<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Db;

use Closure;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use SwowCloud\Job\Contract\EventDispatcherInterface;
use SwowCloud\Job\Db\Pool\PoolFactory;
use SwowCloud\Job\Event\QueryExecuted;
use Throwable;

/**
 * @method static beginTransaction()
 * @method static commit()
 * @method static rollback()
 * @method static insert(string $query, array $bindings = [])
 * @method static execute(string $query, array $bindings = [])
 * @method static query(string $query, array $bindings = [])
 * @method static fetch(string $query, array $bindings = [])
 * @method static run(Closure $closure)
 */
class DB
{
    protected PoolFactory $factory;

    protected string $poolName;

    public function __construct(PoolFactory $factory, string $poolName = 'default')
    {
        $this->factory = $factory;
        $this->poolName = $poolName;
    }

    public function __call($name, $arguments)
    {
        $hasContextConnection = Context::has($this->getContextKey());
        $connection = $this->getConnection($hasContextConnection);

        try {
            $connection = $connection->getConnection();
            $result = $connection->{$name}(...$arguments);
        } catch (Throwable $exception) {
            $result = $connection->retry($exception, $name, $arguments);
        } finally {
            if (!$hasContextConnection) {
                if ($this->shouldUseSameConnection($name)) {
                    // Should storage the connection to coroutine context, then use defer() to release the connection.
                    Context::set($contextKey = $this->getContextKey(), $connection);
                    defer(static function () use ($connection, $contextKey) {
                        Context::set($contextKey, null);
                        $connection->release();
                    });
                } else {
                    // Release the connection after command executed.
                    $connection->release();
                }
            }
            $this->logQuery($arguments[0], $arguments[1] ?? [], null, $result);
        }

        return $result;
    }

    public static function __callStatic(mixed $name, mixed $arguments)
    {
        $container = ApplicationContext::getContainer();

        return $container->get(static::class)->{$name}(...$arguments);
    }

    /**
     * Make a new connection with the pool name.
     */
    public static function connection(string $poolName): self
    {
        return make(static::class, [
            'poolName' => $poolName,
        ]);
    }

    /**
     * Define the commands that needs same connection to execute.
     * When these commands executed, the connection will storage to coroutine context.
     */
    protected function shouldUseSameConnection(string $methodName): bool
    {
        return in_array($methodName, [
            'beginTransaction',
            'commit',
            'rollBack',
        ]);
    }

    /**
     * Get a connection from coroutine context, or from mysql connectio pool.
     */
    protected function getConnection(bool $hasContextConnection): AbstractConnection
    {
        $connection = null;
        if ($hasContextConnection) {
            $connection = Context::get($this->getContextKey());
        }
        if (!$connection instanceof AbstractConnection) {
            $pool = $this->factory->getPool($this->poolName);
            $connection = $pool->get();
        }

        return $connection;
    }

    /**
     * The key to identify the connection object in coroutine context.
     */
    private function getContextKey(): string
    {
        return sprintf('db.connection.%s', $this->poolName);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function logQuery(mixed $query, array $bindings = [], ?float $time = null, mixed $result = null): void
    {
        if ($query instanceof Closure) {
            try {
                $ref = new \ReflectionFunction($query);
                $static = $ref->getStaticVariables();
                if (array_key_exists('command', $static)) {
                    /**
                     * @var \SwowCloud\Job\Db\Command $command
                     */
                    $command = $static['command'];
                    $query = $command->getSql();
                    $bindings = $command->getParams();
                }
            } catch (\ReflectionException $e) {
                //do not
            }
        }
        if (is_string($query)) {
            ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                ->dispatch(
                    new QueryExecuted($query, $bindings, $time, $result),
                    QueryExecuted::QUERY_EXECUTED
                );
        }
    }
}
