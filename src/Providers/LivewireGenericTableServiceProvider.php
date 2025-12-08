<?php

namespace CakMalik\LivewireGenericTable\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireGenericTableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'livewire-generic-table');

        Livewire::component('generic-table', \CakMalik\LivewireGenericTable\Livewire\GenericTable::class);

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/livewire-generic-table'),
        ], 'livewire-generic-table-views');
    }

    public function register()
    {
        //
    }
}
