<?php

namespace Twipsi\Components\Database;

use Closure;
use Throwable;
use Twipsi\Components\Database\Events\TransactionBeganEvent;
use Twipsi\Components\Database\Events\TransactionCommitedEvent;
use Twipsi\Components\Database\Events\TransactionRolledbackEvent;
use Twipsi\Foundation\Exceptions\ApplicationManagerException;

trait HandlesTransactions
{
    /**
     * Number of transactions executed.
     *
     * @var int
     */
    protected int $transactionDepth = 0;

    /**
     * Create a transaction process.
     *
     * @param Closure $callback
     * @return mixed
     * @throws ApplicationManagerException
     * @throws Throwable
     */
    public function transaction(Closure $callback): mixed
    {
        //Begin the PDO transaction.
        $this->beginTransaction();

        try {
            $result = $callback($this->connection);

        } catch (Throwable $e) {
            $this->handleTransactionException($e);
        }

        try {
            $this->commit();

        } catch (Throwable $e) {
            $this->handleCommitException($e);
        }

        return $result;
    }

    /**
     * Begin PDO transaction.
     *
     * @return void
     * @throws Throwable
     * @throws ApplicationManagerException
     */
    public function beginTransaction(): void
    {
        // If it's our first transaction then begin the transaction
        // otherwise set the save point for nested transactions.
        if($this->transactionDepth == 0) {

            try {
                $this->connection->getPDO()->beginTransaction();

            } catch (Throwable $e) {
                $this->tryTransactionAgain($e);
            }
        }

        // If we are nesting transactions register the save point.
        $this->registerSavePoint($this->transactionDepth);

        // Increment the transaction depth.
        $this->transactionDepth++;

        !isset($this->event)
            ?: $this->event->dispatch(TransactionBeganEvent::class);
    }

    /**
     * Commit PDO transactions.
     *
     * @return void
     * @throws ApplicationManagerException
     */
    public function commit(): void
    {
        // If we are not nesting transactions.
        if($this->transactionDepth == 1) {
            $this->connection->getPDO()->commit();
        }

        // Decrement the transaction depth.
        $this->transactionDepth--;

        // If we are nesting transactions release the save point.
        $this->releaseSavePoint($this->transactionDepth);

        !isset($this->event)
            ?: $this->event->dispatch(TransactionCommitedEvent::class);
    }

    /**
     * Rollback to a transaction level.
     *
     * @param int|null $level
     * @return void
     * @throws ApplicationManagerException
     */
    public function rollback(int $level = null): void
    {
        $level = $level ?? $this->transactionDepth - 1;

        // If it's an invalid level exit;
        if($level < 0 || $level > $this->transactionDepth) {
            return;
        }

        $this->transactionDepth = $level;

        // If we are not nesting transactions.
        if($this->transactionDepth == 0) {
            $this->connection->getPDO()->rollBack();
        }

        // If we are nesting transactions rollback to level.
        $this->rollbackSavePoint($this->transactionDepth);

        !isset($this->event)
            ?: $this->event->dispatch(TransactionRolledbackEvent::class);
    }

    /**
     * Register the level savepoint.
     *
     * @param int $depth
     * @return void
     */
    protected function registerSavePoint(int $depth): void
    {
        if($this->isNestedTransaction() && $this->supportsSavePoints()) {

            $this->connection->getPDO()->exec(
                $this->connection->language()->buildRegisterSavePointExpression($depth)
            );
        }
    }

    /**
     * Release savepoint at a level.
     *
     * @param int $depth
     * @return void
     */
    protected function releaseSavePoint(int $depth): void
    {
        if($this->isNestedTransaction() && $this->supportsSavePoints()) {

            $this->connection->getPDO()->exec(
                $this->connection->language()->buildReleaseSavePointExpression($depth)
            );
        }
    }

    /**
     * Rollback to savepoint at a level.
     *
     * @param int $depth
     * @return void
     */
    protected function rollbackSavePoint(int $depth): void
    {
        if($this->isNestedTransaction() && $this->supportsSavePoints()) {

            $this->connection->getPDO()->exec(
                $this->connection->language()->buildRollbackSavePointExpression($depth)
            );
        }
    }

    /**
     * Try transaction again or thor error.
     *
     * @param Throwable $e
     * @return void
     * @throws Throwable
     */
    protected function tryTransactionAgain(Throwable $e): void
    {
        if (DatabaseDriver::isConnectionError($e)) {

            $this->connection->reconnect();
            $this->connection->getPDO()->beginTransaction();

        } else {
            throw $e;
        }
    }

    /**
     * Rollback and throw the exception.
     *
     * @param Throwable $e
     * @return void
     * @throws ApplicationManagerException
     * @throws Throwable
     */
    protected function handleTransactionException(Throwable $e): void
    {
        $this->rollback();

        throw $e;
    }

    /**
     * Handle exception on commit.
     *
     * @param Throwable $e
     * @return void
     * @throws Throwable
     */
    protected function handleCommitException(Throwable $e): void
    {
        // Decrement the transaction depth.
        $this->transactionDepth--;

        if (DatabaseDriver::isConnectionError($e)) {
            $this->transactionDepth = 0;
        }

        throw $e;
    }

    /**
     * Check if the driver supports save points.
     *
     * @return bool
     */
    protected function supportsSavePoints(): bool
    {
        return $this->connection->language()->supportsSavePoints();
    }

    /**
     * Flush the transaction count.
     *
     * @return void
     */
    public function flushTransactions(): void
    {
        $this->transactionDepth = 0;
    }

    /**
     * Check if we are doing nested transactions.
     *
     * @return bool
     */
    public function isNestedTransaction(): bool
    {
        return $this->transactionDepth > 0;
    }
}