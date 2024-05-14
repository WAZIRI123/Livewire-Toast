<?php

namespace Waziri123\WaziriLivewireToast\Tests;


use Christophrumpel\MissingLivewireAssertions\MissingLivewireAssertionsServiceProvider;
use Livewire\LivewireServiceProvider;
use Waziri123\WaziriLivewireToast\LivewireToastServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{


    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            LivewireToastServiceProvider::class,
            MissingLivewireAssertionsServiceProvider::class,
        ];
    }

 
}