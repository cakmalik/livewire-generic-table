<?php

declare(strict_types=1);

namespace Cakmalik\LivewireGenericTable;

use Cakmalik\LivewireGenericTable\Http\Livewire\GenericTable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LivewireGenericTableServiceProvider extends PackageServiceProvider
{
  public function configurePackage(Package $package): void
  {
    $package
      ->name('livewire-generic-table')
      ->hasViews();
  }

  public function bootingPackage(): void
  {
    $this->registerLivewireComponent();
  }

  protected function registerLivewireComponent(): void
  {
    Livewire::component('generic-table', GenericTable::class);
  }
}
