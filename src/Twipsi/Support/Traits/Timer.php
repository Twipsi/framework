<?php

namespace Twipsi\Support\Traits;

trait Timer
{
    /**
     * Time when we started.
     *
     * @var float
     */
    private float $start;

    /**
     * Starts the timer.
     * 
     * @return void
     */
    protected function startTimer(): void
    {
        $this->start = microtime(true);
    }

    /**
     * Stop the timer and return the duration.
     *
     * @return float
     */
    protected function stopTimer(): float
    {
        return round((microtime(true) - $this->start) * 1000, 2);
    }
}