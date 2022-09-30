<?php

namespace Twipsi\Components\Database;

use Twipsi\Support\Str;

trait LogsQueries
{
    /**
     * The query log container.
     *
     * @var array
     */
    protected array $queryLog = [];

    /**
     * Weather we should log queries.
     *
     * @var bool
     */
    protected bool $shouldLogQueries = true;

    /**
     * Log a query.
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     */
    public function logQuery(string $query, array $bindings, float $time): void
    {
        $query = $this->formatQuery($query, $bindings);

        $this->queryLog[] = compact('query', 'time');
    }

    /**
     * Replace the query bindings to their values.
     *
     * @param string $query
     * @param array $bindings
     * @return string
     */
    protected function formatQuery(string $query, array $bindings): string
    {
        $bindings = $this->processBindings($bindings);

        array_walk($bindings, function ($v, $k) use(&$result) {
            $result[':'.$k] = $v;
        });

        return Str::hay($query)
                ->replace(array_keys($result ?? []), array_values($result ?? []));
    }

    /**
     * Get the query log container.
     *
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Reset the log container.
     *
     * @return void
     */
    public function resetQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Check if we are logging the queries.
     *
     * @return bool
     */
    public function isLoggingQueries(): bool
    {
        return $this->shouldLogQueries;
    }

    /**
     * Turn on query logging.
     *
     * @return void
     */
    public function enableQueryLog(): void
    {
        $this->shouldLogQueries = true;
    }

    /**
     * Turn off query logging.
     *
     * @return void
     */
    public function disableQueryLog(): void
    {
        $this->shouldLogQueries = false;
    }
}