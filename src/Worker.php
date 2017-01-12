<?php

namespace Mvdstam\GracefulLaravelWorkers;

use function Mvdstam\GracefulLaravelWorkers\shutting_down;

class Worker extends \Illuminate\Queue\Worker
{

    /**
     * @inheritdoc
     */
    public function daemon($connectionName, $queue = null, $delay = 0, $memory = 128, $sleep = 3, $maxTries = 0)
    {
        $this->installSignalHandlers();

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            if (shutting_down()) {
                $this->stop();
                return;
            }

            if ($this->daemonShouldRun()) {
                $this->runNextJobForDaemon(
                    $connectionName, $queue, $delay, $sleep, $maxTries
                );
            } else {
                $this->sleep($sleep);
            }

            if ($this->memoryExceeded($memory) || $this->queueShouldRestart($lastRestart)) {
                $this->stop();
                return;
            }
        }
    }

    /**
     * Installs signal handlers for the current process
     *
     * @inheritdoc
     */
    protected function installSignalHandlers()
    {
        foreach (config('graceful-laravel-workers.signals') as $signal) {
            pcntl_signal($signal, '\Mvdstam\GracefulLaravelWorkers\shutting_down');
        }
    }

}
