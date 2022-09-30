<?php

namespace Twipsi\Components\Database;

use Closure;

trait MocksQueries
{
    /**
     * Weather we are mocking or not.
     *
     * @var bool
     */
    protected bool $mocking = false;

    /**
     * Run the callback without actually running the dispatcher.
     *
     * @param Closure $callback
     * @return array
     */
    public function mock(Closure $callback) : array
    {
        return $this->startQueryLog(function () use ($callback) {

            $this->mocking = true;

            call_user_func($callback, $this->connection);

            $this->mocking = false;

            return $this->getQueryLog();
        });
    }

    /**
     * Start the logging process.
     *
     * @param Closure $mock
     * @return array
     */
    protected function startQueryLog(Closure $mock) : array
    {
        if(! $this->isLoggingQueries()) {

            $this->enableQueryLog();
            $memory = true;
        }

        $this->resetQueryLog();

        $result = call_user_func($mock);

        !isset($memory) ?: $this->disableQueryLog();

        return $result;
    }

    /**
     * Check if we are mocking.
     *
     * @return bool
     */
    public function mocking(): bool
    {
        return $this->mocking ?? false;
    }
}