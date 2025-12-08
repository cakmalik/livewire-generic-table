# Generic Livewire Table Component

A flexible, Volt-friendly, and fully customizable table component for **Laravel Livewire v3**.  
Designed for reusable admin tables with sorting, searching, actions, pagination, and custom column formatting.

---

## 🚀 Features

- Works with both **Livewire** and **Livewire Volt**
- Fully customizable column configuration array
- Searchable and sortable columns
- Badge formats with dynamic colors & labels
- Image, badge, datetime, link, and custom renderers
- Row actions with event emission
- Pagination with configurable per‑page options
- Easy integration into any Laravel app or package

---

## 📦 Installation

Install via Composer:

```bash
composer require your-vendor/generic-table
```

If using Laravel < 10.21, publish views:

```bash
php artisan vendor:publish --tag=generic-table-views
```

---

## 🧩 Usage

### In Blade

```blade
<livewire:generic-table
    :model="'App\\Models\\Post'"
    :columns="$columns"
    :queryParams="['keyword' => request('keyword')]"
    defaultSortField="created_at"
    defaultSortDirection="desc"
/>
```

### In Volt

Because Volt does not allow nested arrays inside state, you **must use a computed property**:

```php
#[Computed]
public function columns(): array
{
    return [
        [
            'label' => 'Title',
            'field' => 'title',
            'sortable' => true,
            'searchable' => true,
        ],
        [
            'label' => 'Status',
            'field' => 'status',
            'format' => 'badge',
            'badge' => [
                'colors' => [
                    'draft' => 'bg-gray-100 text-gray-700',
                    'published' => 'bg-green-100 text-green-800',
                ],
                'labels' => [
                    'draft' => 'Draft',
                    'published' => 'Published',
                ],
                'default' => 'bg-gray-200 text-gray-900',
                'default_label' => 'Unknown',
            ],
        ],
    ];
}
```

Then use it:

```blade
<livewire:generic-table
    :model="$this->modelName"
    :columns="$this->columns"
    :queryParams="[
        'keyword' => request('keyword')
    ]"
/>
```

---

## 🛠 Column Options

Each column supports:

| Key | Description |
|-----|-------------|
| `label` | Column header text |
| `field` | Column data field (supports nested `relation.field`) |
| `sortable` | Enable sorting |
| `searchable` | Enable keyword searching |
| `format` | `text`, `image`, `badge`, `datetime`, `custom` |
| `badge` | Configuration for badge colors & labels |
| `actions` | Row action buttons |

### Example with actions

```php
[
    'label' => 'Actions',
    'field' => 'actions',
    'actions' => [
        [
            'event' => 'editAction',
            'icon' => 'pencil',
            'color' => 'indigo',
            'variant' => 'primary',
        ],
        [
            'event' => 'deleteAction',
            'icon' => 'trash',
            'color' => 'red',
            'variant' => 'danger',
        ],
    ],
],
```

---

## 📡 Events

Your component may listen to row action events:

```php
#[On('editAction')]
public function edit($id)
{
    // handle edit
}
```

---

## 🎨 Custom Styling

You may override the view:

```bash
php artisan vendor:publish --tag=generic-table-views
```

Then edit:

```
resources/views/vendor/generic-table/generic-table.blade.php
```

---

## 🤝 Contributing

Pull requests are welcome!  
If you want to improve functionality, fix bugs, or extend features—feel free to contribute.

---

## 📜 License

This package is open-sourced under the **MIT License**.
