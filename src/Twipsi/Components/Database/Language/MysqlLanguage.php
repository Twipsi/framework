<?php

namespace Twipsi\Components\Database\Language;

use Twipsi\Components\Database\Builder\QueryBuilder;
use Twipsi\Foundation\Exceptions\NotSupportedException;

final class MysqlLanguage extends Language
{
    /**
     * Check if the driver supports save points.
     *
     * @return bool
     */
    public function supportsSavePoints(): bool
    {
        return true;
    }

    /**
     * Get the statement scheme map to build.
     *
     * @param string $statement
     * @return string
     * @throws NotSupportedException
     */
    public function getScheme(string $statement): string
    {
        return match ($statement) {
            'insert' => "insert into {table} {insertColumns} {values}",
            'ignore' => "insert ignore into {table} {insertColumns} {values}",
            'upsert' => "insert into {table} {insertColumns} {values} on duplicate key update {updateColumns}",
            'select' => "select {aggregate} {columns} from {table} {joins} {where} {groups} {havings} {orders} {limit} {offset}",
            'update' => "update {table} {join} set {updateColumns} {where} {orders} {limit}",
            'delete' => "delete {alias} from {table} {join} {where} {orders} {limit}",
            default => throw new NotSupportedException(sprintf("Statement '%s' not supported", $statement)),
        };
    }

    /**
     * Build an insert statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NotSupportedException
     */
    public function toInsertIgnore(QueryBuilder $query): string
    {
        $scheme = $this->toExpression($query, 'ignore');
        return $scheme;
    }

    /**
     * Build the values of an insert statement.
     *
     * @param QueryBuilder $query
     * @return string
     */
    public function toValues(QueryBuilder $query): string
    {
        if(empty($query->bindings)) {
            return 'values ()';
        }

        return "values ({$this->tokenize($query->columns)})";
    }

    /**
     * Build an upsert statement based on query builder data.
     *
     * @param QueryBuilder $query
     * @return string
     * @throws NotSupportedException
     */
    public function toUpsert(QueryBuilder $query): string
    {
        $scheme = $this->toExpression($query, 'upsert');
        return $scheme;
    }

    /**
     * Wrap a value in quotes.
     *
     * @param string $value
     * @return string
     */
    protected function quoteValue(string $value): string
    {
        return ($value !== '*')
            ? '`'.str_replace('`', '``', $value).'`'
            : $value;
    }
}