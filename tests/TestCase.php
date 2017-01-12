<?php


namespace Mvdstam\GracefulLaravelWorkers\Tests;


use Mvdstam\GracefulLaravelWorkers\Providers\GracefulLaravelWorkersServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->register(GracefulLaravelWorkersServiceProvider::class);

        // Load config files
        foreach (glob(dirname(__DIR__, 1) . '/config/*.php') as $file) {
            /** @noinspection PhpIncludeInspection */
            $app['config']->set(
                pathinfo($file, PATHINFO_FILENAME),
                require $file
            );
        }
    }

    public function tearDown()
    {
        \Mvdstam\GracefulLaravelWorkers\shutting_down(null, true);

        parent::tearDown();
    }
}
