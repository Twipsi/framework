<?php
declare(strict_types=1);

/*
 * This file is part of the Twipsi package.
 *
 * (c) Petrik Gábor <twipsi@twipsi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twipsi\Components\Validator;

use Twipsi\Components\Database\Builder\QueryBuilder;
use Twipsi\Components\Database\Interfaces\IDatabaseConnection;
use Twipsi\Support\Str;

class DatabaseVerifier
{
    /**
     *  DatabaseVerifier constructor
     */
    public function __construct(protected IDatabaseConnection $connection) {}

    /**
     * Count the occurance of an expression.
     * 
     * @param string $table
     * @param string $column
     * @param string $condition
     * @param int|null $excludeId
     * @param string|null $excludeCol
     * @param array $extra
     * @param array $conditions
     * 
     * @return QueryBuilder
     */
    public function count(string $table, string $column, string $condition, 
                            ?int $excludeId = null, ?string $excludeCol = null, array $extra = []): int 
    {
        $conditions = array_merge([$column => $condition], $extra);

        $query = $this->condition($this->open($table), $conditions);

        if(! is_null($excludeId)) {
            $query->where($excludeCol ?: 'id', '<>', $excludeId);
        }
        
        return $query->count();
    }

    /**
     * Add Conditions to the query.
     * 
     * @param QueryBuilder $query
     * @param array $conditions
     * 
     * @return QueryBuilder
     */
    protected function condition(QueryBuilder $query, array $conditions): QueryBuilder
    {
        foreach($conditions as $column => $condition) {
            $this->where($query, $column, $condition);
        }

        return $query;
    }

    /**
     * Set where data based on provided conditions.
     * 
     * @param QueryBuilder $query
     * @param string $column
     * @param string $condition
     * 
     * @return QueryBuilder
     */
    protected function where(QueryBuilder $query, string $column, string $condition): QueryBuilder
    {
        if($condition === 'NULL') {
            return $query->whereNull($column);
        }

        if($condition === 'NOT_NULL') {
            return $query->whereNotNull($column);
        }

        if(Str::hay($condition)->first('!')) {
            return $query->where($column, '!=', ltrim($condition, '!'));
        }

        return $query->where($column, '=', $condition);
    }

    /**
     * Open a connection to a table.
     * 
     * @param string $table
     * 
     * @return QueryBuilder
     */
    protected function open(string $table): QueryBuilder
    {
        return $this->connection->open($table);
    }
}