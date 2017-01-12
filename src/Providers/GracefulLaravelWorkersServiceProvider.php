<?php


namespace Mvdstam\GracefulLaravelWorkers\Providers;


use Illuminate\Queue\QueueServiceProvider;
use Mvdstam\GracefulLaravelWorkers\Worker;

class GracefulLaravelWorkersServiceProvider extends QueueServiceProvider
{

    /**
     * @codeCoverageIgnore
     */
    protected function registerWorker()
    {
        parent::registerWorker();

        $this->app->singleton('queue.worker', function ($app) {
            return new Worker($app['queue'], $app['queue.failer'], $app['events']);
        });
    }

    /**
     * @codeCoverageIgnore
     */
    public function boot()
    {
        $publishedFiles = [];
        foreach (glob(realpath(dirname(__DIR__, 2).'/config') . '/*.php') as $file) {
            $publishedFiles[$file] = config_path(pathinfo($file, PATHINFO_BASENAME));
        }

        $this->publishes($publishedFiles, 'graceful-laravel-workers');
    }
}
