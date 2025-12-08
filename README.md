# Livewire Generic Table by CakMalik

Reusable Livewire 3 Generic Table Component for Laravel.

## Installation
```
composer require cakmalik/livewire-generic-table
```

## Usage
```
<livewire:generic-table 
    :query="\App\Models\User::query()" 
    :columns="[
        ['label' => 'Name', 'field' => 'name'],
        ['label' => 'Email', 'field' => 'email'],
    ]"
/>
```

## Publish Views
```
php artisan vendor:publish --tag=livewire-generic-table-views
```

## License
MIT
