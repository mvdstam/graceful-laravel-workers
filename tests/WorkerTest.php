<?php


namespace Mvdstam\GracefulLaravelWorkers\Tests;


use Exception;
use Mockery;
use Mockery\Mock;
use Mvdstam\GracefulLaravelWorkers\Worker;

use function Mvdstam\GracefulLaravelWorkers\shutting_down;

class WorkerTest extends TestCase
{

    /**
     * @var Worker
     */
    protected $worker;

    public function setUp()
    {
        parent::setUp();

        $this->worker = Mockery::mock(Worker::class)->shouldAllowMockingProtectedMethods()->makePartial();
    }

    /**
     * @param $signal
     * @dataProvider signalDataProvider
     */
    public function testDaemonStopsWhenSignalsWereCaught($signal)
    {
        /**
         * @var Mock $workerMock
         */
        $workerMock = $this->worker;
        
        $workerMock
            ->shouldReceive('daemonShouldRun')
            ->andReturnUsing(function() use ($signal) {
                if (!posix_kill(getmypid(), $signal)) {
                    throw new Exception('Unable send POSIX signal to PHP process');
                }

                return true;
            });

        $workerMock
            ->shouldReceive('runNextJobForDaemon');

        $workerMock
            ->shouldReceive('memoryExceeded')
            ->andReturn(false);

        $workerMock
            ->shouldReceive('queueShouldRestart')
            ->andReturn(false);

        $workerMock
            ->shouldReceive('stop')
            ->once();

        $this->assertFalse(shutting_down());

        $this->worker->daemon('phpunit');

        $this->assertTrue(shutting_down());

        $workerMock
            ->shouldHaveReceived('stop')
            ->once();

        $workerMock
            ->shouldHaveReceived('runNextJobForDaemon')
            ->once();
    }

    public function signalDataProvider()
    {
        return [
            [SIGINT],
            [SIGTERM]
        ];
    }
}
