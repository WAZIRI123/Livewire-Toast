<?php

namespace Orchestra\Testbench\Concerns;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Console\Application as Artisan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\View\Component;
use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Throwable;

trait ApplicationTestingHooks
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application|null
     */
    protected $app;

    /**
     * The callbacks that should be run after the application is created.
     *
     * @var array<int, callable():void>
     */
    protected $afterApplicationCreatedCallbacks = [];

    /**
     * The callbacks that should be run after the application is refreshed.
     *
     * @var array<int, callable():void>
     */
    protected $afterApplicationRefreshedCallbacks = [];

    /**
     * The callbacks that should be run before the application is destroyed.
     *
     * @var array<int, callable():void>
     */
    protected $beforeApplicationDestroyedCallbacks = [];

    /**
     * The exception thrown while running an application destruction callback.
     *
     * @var \Throwable|null
     */
    protected $callbackException;

    /**
     * Setup the testing hooks.
     *
     * @param  (\Closure():(void))|null  $callback
     * @return void
     */
    final protected function setUpTheApplicationTestingHooks(?Closure $callback = null): void
    {
        if (! $this->app) {
            $this->refreshApplication();

            $this->setUpParallelTestingCallbacks();
        }

        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $this->callAfterApplicationRefreshedCallbacks();

        if (! \is_null($callback)) {
            \call_user_func($callback);
        }

        $this->callAfterApplicationCreatedCallbacks();

        Model::setEventDispatcher($app['events']);
    }

    /**
     * Teardown the testing hooks.
     *
     * @param  (\Closure():(void))|null  $callback
     * @return void
     *
     * @throws \Throwable
     */
    final protected function tearDownTheApplicationTestingHooks(?Closure $callback = null): void
    {
        if ($this->app) {
            $this->callBeforeApplicationDestroyedCallbacks();

            $this->tearDownParallelTestingCallbacks();

            $this->app?->flush();

            $this->app = null;
        }

        if (! \is_null($callback)) {
            \call_user_func($callback);
        }

        if (class_exists(Mockery::class) && $this instanceof PHPUnitTestCase) {
            if ($container = Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }

            Mockery::close();
        }

        Carbon::setTestNow();

        if (class_exists(CarbonImmutable::class)) {
            CarbonImmutable::setTestNow();
        }

        $this->afterApplicationCreatedCallbacks = [];
        $this->beforeApplicationDestroyedCallbacks = [];

        Artisan::forgetBootstrappers();
        Component::flushCache();
        Component::forgetComponentsResolver();
        Component::forgetFactory();
        Queue::createPayloadUsing(null);
        HandleExceptions::forgetApp();

        if ($this->callbackException) {
            throw $this->callbackException;
        }
    }

    /**
     * Setup parallel testing callback.
     */
    protected function setUpParallelTestingCallbacks(): void
    {
        if (class_exists(ParallelTesting::class) && $this instanceof PHPUnitTestCase) {
            /** @phpstan-ignore-next-line */
            ParallelTesting::callSetUpTestCaseCallbacks($this);
        }
    }

    /**
     * Teardown parallel testing callback.
     */
    protected function tearDownParallelTestingCallbacks(): void
    {
        if (class_exists(ParallelTesting::class) && $this instanceof PHPUnitTestCase) {
            /** @phpstan-ignore-next-line */
            ParallelTesting::callTearDownTestCaseCallbacks($this);
        }
    }

    /**
     * Register a callback to be run after the application is refreshed.
     *
     * @param  callable():void  $callback
     * @return void
     */
    protected function afterApplicationRefreshed(callable $callback): void
    {
        $this->afterApplicationRefreshedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            \call_user_func($callback);
        }
    }

    /**
     * Execute the application's post-refreshed callbacks.
     *
     * @return void
     */
    protected function callAfterApplicationRefreshedCallbacks(): void
    {
        foreach ($this->afterApplicationRefreshedCallbacks as $callback) {
            \call_user_func($callback);
        }
    }

    /**
     * Register a callback to be run after the application is created.
     *
     * @param  callable():void  $callback
     * @return void
     */
    protected function afterApplicationCreated(callable $callback): void
    {
        $this->afterApplicationCreatedCallbacks[] = $callback;

        if ($this->setUpHasRun) {
            \call_user_func($callback);
        }
    }

    /**
     * Execute the application's post-creation callbacks.
     *
     * @return void
     */
    protected function callAfterApplicationCreatedCallbacks(): void
    {
        foreach ($this->afterApplicationCreatedCallbacks as $callback) {
            \call_user_func($callback);
        }
    }

    /**
     * Register a callback to be run before the application is destroyed.
     *
     * @param  callable():void  $callback
     * @return void
     */
    protected function beforeApplicationDestroyed(callable $callback): void
    {
        array_unshift($this->beforeApplicationDestroyedCallbacks, $callback);
    }

    /**
     * Execute the application's pre-destruction callbacks.
     *
     * @return void
     */
    protected function callBeforeApplicationDestroyedCallbacks(): void
    {
        foreach ($this->beforeApplicationDestroyedCallbacks as $callback) {
            try {
                \call_user_func($callback);
            } catch (Throwable $e) {
                if (! $this->callbackException) {
                    $this->callbackException = $e;
                }
            }
        }
    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    abstract protected function refreshApplication();
}
