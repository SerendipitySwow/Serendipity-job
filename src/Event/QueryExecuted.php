<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Event;

class QueryExecuted
{
    public const QUERY_EXECUTED = 'query-executed';

    /**
     * The SQL query that was executed.
     */
    public ?string $sql;

    /**
     * The array of query bindings.
     */
    public ?array $bindings;

    /**
     * The number of milliseconds it took to execute the query.
     */
    public ?float $time;

    /**
     * The result of query.
     */
    public int|array|null|\Throwable $result;

    /**
     * Create a new event instance.
     */
    public function __construct(string $sql, array $bindings, ?float $time = null, \Throwable|array|int $result = null)
    {
        $this->sql = $sql;
        $this->time = $time;
        $this->bindings = $bindings;
        $this->result = $result;
    }
}
