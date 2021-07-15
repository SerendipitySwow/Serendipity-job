<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Db;

use Serendipity\Job\Db\Exception\RuntimeException;

class Command
{
    private string $sql;

    private array $params = [];

    public function insert(string $table, array $columns): string
    {
        $fields = array_map(function ($value) {
            return $this->quoteColumn($value);
        }, array_keys($columns));
        $placeholder = array_map(static function ($value) {
            return ':' . strtoupper($value);
        }, array_keys($columns));
        $this->sql = 'INSERT INTO ' . $this->quoteTable($table) . ' (' . implode(
            ',',
            $fields
        ) . ') VALUES (' . implode(',', $placeholder) . ')';
        $this->params = [];
        foreach ($columns as $k => $v) {
            $this->params[':' . strtoupper($k)] = $v;
        }

        return $this->prepare();
    }

    /**
     * @param array $params to be bound in condition,could be set to empty array
     */
    public function update(string $table, array $columns, string $condition, array $params = []): ?string
    {
        $sets = array_map(function ($value) {
            return $this->quoteColumn($value) . ' = :' . strtoupper($value);
        }, array_keys($columns));
        $this->sql = 'UPDATE ' . $this->quoteTable($table) . ' SET ' . implode(',', $sets);
        if (is_string($condition)) {
            $this->sql .= ' WHERE ' . $condition;
        } else {
            throw new RuntimeException('condition must be a string');
        }

        $this->params = $params;
        foreach ($columns as $k => $v) {
            $this->params[':' . strtoupper($k)] = $v;
        }
    }

    /**
     * delete from a table by some condition
     *
     * @param array $params to be bound in condition
     *
     * @throws DbException
     */
    public function delete(string $table, string $condition, array $params = []): string
    {
        $this->sql = 'DELETE FROM ' . $this->quoteTable($table);
        if (is_string($condition)) {
            $this->sql .= ' WHERE ' . $condition;
        } else {
            throw new DbException('condition must be a string');
        }
        $this->params = $params;
    }

    public function quoteColumn($name): ?string
    {
        $name = trim((string) $name, '`');
        if (str_contains($name, '`')) {
            throw new RuntimeException("column name must not contain any character `, {$name} is given");
        }

        return (string) $name === '*' ? (string) $name : "`{$name}`";
    }

    public function quoteTable($name): ?string
    {
        $name = trim((string) $name, '`');
        if (str_contains($name, '`')) {
            throw new RuntimeException("table name must not contain any character `, {$name} is given");
        }

        return "`{$name}`";
    }
}
