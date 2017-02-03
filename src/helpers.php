<?php

namespace Mvdstam\GracefulLaravelWorkers;

if (!function_exists(__NAMESPACE__ . '\\shutting_down')) {
    /**
     * Stateful function which can be used in conjunction
     * with signal handling, so the application has a clean
     * way to determine whether or not it should continue
     * operating.
     *
     * The $reset parameter overrides any current state and
     * should not be used except for testing purposes.
     *
     * @param null $set
     * @param bool $reset
     * @return bool
     */
    function shutting_down($set = null, $reset = false)
    {
        pcntl_signal_dispatch();

        static $shuttingDown;

        if (!is_null($set)) {
            $shuttingDown = true;
        } elseif ($reset) {
            $shuttingDown = false;
        }

        return (boolean)$shuttingDown;
    }
}
