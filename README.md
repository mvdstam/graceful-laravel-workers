# Graceful Laravel Workers

Stopping your Workers and Jobs without hurting your application.

In many modern webapplications, many tasks are handled asynchronously through the use of some kind of queueing system and jobs.
In Laravel and Lumen-based applications, this is possible through the use of the `illuminate/queue` component.
This package expands a little bit upon that component by making it possible to *gracefully* stop Workers (or any kind of process for that matter) through the use of [Posix Signals](https://en.wikipedia.org/wiki/Unix_signal).
This also enables your queue workers to be run inside of [Docker containers](https://www.docker.com/) and be stopped in a graceful and clean way, since Docker utilizes posix signals to stop running containers when, for example, updating them.

Finally, gracefully cleaning up running processes is a crucial part of any (12-factor application)[https://12factor.net/].

### Why is this important?
When a queue worker is started through `php artisan queue:work --daemon`, a *long-running* PHP process is started.
This process will run endlessly until it is stopped by either external sources (posix signals) or internal sources (the process crashes or simply stops because of an `exit;`).

Usually, these PHP processes are stopped externally by sending `SIGTERM` or `SIGINT` signals to the underlying processes.
The default behaviour of PHP is to stop the process **immediately**, which means that any kind of important process is interrupted and possibly your data is lost.
By *trapping* these signals with the use of the [PCNTL](http://php.net/manual/en/book.pcntl.php) extension, we can *catch* these signals and define custom behaviour when this happens.
This is especially interesting when running queue workers within Docker containers, since containers are ephemeral by design and can (should) be replaced at any time.

### shutting_down()
The most important aspect of this package is the function `shutting_down()`. In your long-running jobs, simply call `shutting_down()` in each iteration.
This function returns `TRUE` when a signal has been caught and your application should prepare to shut down immediately. For example:

```php
<?php

use function Mvdstam\GracefulLaravelWorkers\shutting_down;

class MyJob implements \Illuminate\Contracts\Queue\ShouldQueue {

    use \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable;

    public function handle()
    {
        while(true) {
            if (shutting_down()) {
                return $this->shutDown();
            }

            $this->handleIteration();
        }
    }

    protected function shutDown()
    {
        // Do some kind of cleaning up, write to log, etc..
        echo 'Saving state and shutting down!';

        /*
         * Sometimes, this job may be continued later on if necessary. Simply dispatch a new instance
         * unto the queue to be picked up later.
         */
        dispatch(new static);
    }

    protected function handleIteration()
    {
        // Do something expensive, such as working on large data sets
    }
}
```

## Version compatibility
This package is compatible with Lumen/Laravel 5.1 (LTS) and Lumen/Laravel 5.2.
Version 5.3 is not supported at the time of writing.

## Requirements

- PHP >=5.6
- The [PCNTL](http://php.net/manual/en/book.pcntl.php) extension

## Install
Via composer

```bash
$ composer require mvdstam/graceful-laravel-workers
```

Add the `GracefulLaravelWorkersServiceProvider` to your `app.php`:

```php
        [..]
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers
         */
        Mvdstam\GracefulLaravelWorkers\Providers\GracefulLaravelWorkersServiceProvider::class,
```

In order for the package to be correctly initialized, please make sure to add the `GracefulLaravelWorkersServiceProvider` **after** the `Illuminate\Queue\QueueServiceProvider`.

Finally, run `php artisan vendor:publish`.

## Usage
Start your worker with `php artisan queue:work --daemon` and try sending `SIGINT` or `SIGTERM` signals to that process when it picks up a job.
When the process is in the foreground, simply press `ctrl+c` to send a `SIGINT`.

## Gotchas
- Always run the queue worker with the `--daemon` flag.
This package only adds to the behaviour of the queue worker when it runs in daemon mode.
- Don't do an `exit;` in your jobs when you're done cleaning up. Simply return from the `handle()` function and let the `Worker` stop the process.
This allows your application to have a consistent point of entry as well as an exitpoint.