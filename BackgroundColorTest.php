<?php

namespace Waziri123\WaziriLivewireToast\Tests;


use Livewire\Livewire;
use ReflectionClass;
use Waziri123\WaziriLivewireToast\Http\Livewire\LivewireToast;


class BackgroundColorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
     // additional setup
      
    }

    
public function test_setType()
{
    Livewire::test(LivewireToast::class)
    ->call('_setType','warning')
    ->assertSet('type', 'warning');

}



}