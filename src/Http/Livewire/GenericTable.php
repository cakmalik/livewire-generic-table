<?php

declare(strict_types=1);

namespace Cakmalik\LivewireGenericTable\Http\Livewire;

use Illuminate\Contracts\View\View;

use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class GenericTable extends Component
{
  use WithPagination;

  protected string $paginationTheme = 'tailwind';
  protected $paginationName = 'tablePage';

  public string $model;
  public array $columns = [];

  #[Reactive]
  public array $queryParams = [];

  public array $baseConditions = [];

  public ?string $defaultSortField = null;
  public string $defaultSortDirection = 'desc';

  public $customQueryCallback = null;

  public string $search = '';
  public ?string $sortField = null;
  public string $sortDirection = 'asc';
  public int $perPage = 10;

  public ?bool $filterPerPage = true;

  public ?string $permissionPrefix = null;

  protected $queryString = [
    'search',
    'sortField',
    'sortDirection',
    'perPage',
    'page' => ['as' => 'tablePage']
  ];

  public function mount(
    string $model = null,
    array $columns = [],
    array $queryParams = [],
    array $baseConditions = [],
    ?string $defaultSortField = null,
    string $defaultSortDirection = 'desc',
    $customQueryCallback = null
  ) {

    if ($this->permissionPrefix) {
      $this->authorize($this->permissionPrefix . '.view_all');
    }

    if ($model) $this->model = $model;
    $this->columns = $columns;
    $this->queryParams = $queryParams;
    $this->baseConditions = $baseConditions;
    $this->defaultSortField = $defaultSortField;
    $this->defaultSortDirection = $defaultSortDirection;
    $this->customQueryCallback = $customQueryCallback;

    if (!$this->sortField && $this->defaultSortField) {
      $this->sortField = $this->defaultSortField;
      $this->sortDirection = $this->defaultSortDirection;
    }
  }

  protected function applyBaseConditions(Builder $query)
  {
    if (empty($this->baseConditions)) return;

    foreach ($this->baseConditions as $condition) {
      if (!is_array($condition) || count($condition) < 2) continue;

      $field = $condition[0];
      $operator = count($condition) === 3 ? $condition[1] : '=';
      $value = count($condition) === 3 ? $condition[2] : $condition[1];

      // Jika field mengandung relasi, misal 'roles.name'
      if (str_contains($field, '.')) {
        [$relation, $relField] = explode('.', $field, 2);

        // 🧠 CASE 1: Operator custom "not"
        if ($operator === 'not') {
          $query->whereHas($relation) // wajib punya relasi
            ->whereDoesntHave($relation, function ($sub) use ($relField, $value) {
              $sub->where($relField, '=', $value);
            });
          continue;
        }

        // 🧠 CASE 2: Operator biasa (misal '=', '!=', '>', '<', 'like', dll)
        $query->whereHas($relation, function ($sub) use ($relField, $operator, $value) {
          $sub->where($relField, $operator, $value);
        });
      } else {
        // 🧠 CASE 3: Field biasa tanpa relasi
        $query->where($field, $operator, $value);
      }
    }
  }

  protected function applyQueryParams(Builder $query)
  {
    if (empty($this->queryParams)) return;

    foreach ($this->queryParams as $field => $value) {
      if ($value === null || $value === '') continue;

      // Support: local scope method on model (scopeXxx)
      $scopeMethod = 'scope' . ucfirst($field);
      if (method_exists($this->model, $scopeMethod)) {
        // Memanggil local scope: $query->field($value)
        $query->{$field}($value);
        continue;
      }

      // Support: _range (expects array [start, end])
      if (str_ends_with($field, '_range') && is_array($value) && count($value) === 2) {
        $actualField = str_replace('_range', '', $field);
        [$startDate, $endDate] = $value;

        if ($startDate && $endDate) {
          $start = Carbon::parse($startDate)->startOfDay();
          $end   = Carbon::parse($endDate)->endOfDay();

          if (str_contains($actualField, '.')) {
            // Relation range: e.g. user.created_at_range
            $parts = explode('.', $actualField);
            $column = array_pop($parts);
            $relations = implode('.', $parts);

            $query->whereHas($relations, function ($sub) use ($column, $start, $end) {
              $sub->whereBetween($column, [$start, $end]);
            });
          } else {
            $query->whereBetween($actualField, [$start, $end]);
          }
        }

        continue;
      }

      // Support: array value => whereIn (also support relation.field => whereIn)
      if (is_array($value)) {
        if (str_contains($field, '.')) {
          $parts = explode('.', $field);
          $column = array_pop($parts);
          $relations = implode('.', $parts);

          $query->whereHas($relations, function ($sub) use ($column, $value) {
            $sub->whereIn($column, $value);
          });
        } else {
          $query->whereIn($field, $value);
        }
        continue;
      }

      // Support: multi-level relation (e.g. userMembership.membership.name)
      if (str_contains($field, '.')) {
        $parts = explode('.', $field);
        $column = array_pop($parts);
        $relations = implode('.', $parts);

        $query->whereHas($relations, function ($sub) use ($column, $value) {
          $sub->where($column, $value);
        });

        continue;
      }

      // Default: plain where on main table
      $query->where($field, $value);
    }
  }

  // protected function applyQueryParams(Builder $query)
  // {
  //     if (empty($this->queryParams)) return;
  //
  //     foreach ($this->queryParams as $field => $value) {
  //         if ($value === null || $value === '') continue;
  //
  //         $scopeMethod = 'scope' . ucfirst($field);
  //
  //         if (method_exists($this->model, $scopeMethod)) {
  //             $query->{$field}($value);
  //         } elseif (str_ends_with($field, '_range') && is_array($value) && count($value) === 2) {
  //             $actualField = str_replace('_range', '', $field);
  //             [$startDate, $endDate] = $value;
  //
  //             if ($startDate && $endDate) {
  //                 // Pastikan waktu dihitung penuh
  //                 $start = Carbon::parse($startDate)->startOfDay();
  //                 $end   = Carbon::parse($endDate)->endOfDay();
  //
  //                 $query->whereBetween($actualField, [$start, $end]);
  //             }
  //         } elseif (is_array($value)) {
  //             $query->whereIn($field, $value);
  //         } elseif (str_contains($field, '.')) {
  //             [$relation, $relField] = explode('.', $field, 2);
  //             $query->whereHas($relation, function ($sub) use ($relField, $value) {
  //                 $sub->where($relField, $value);
  //             });
  //         } else {
  //             $query->where($field, $value);
  //         }
  //     }
  // }
  // protected function applyQueryParams(Builder $query)
  // {
  //     if (empty($this->queryParams)) return;
  //
  //     foreach ($this->queryParams as $field => $value) {
  //         if ($value !== null && $value !== '') {
  //             $scopeMethod = 'scope' . ucfirst($field);
  //
  //             if (method_exists($this->model, $scopeMethod)) {
  //                 $query->{$field}($value);
  //             } elseif (str_ends_with($field, '_range') && is_array($value) && count($value) === 2) {
  //                 $actualField = str_replace('_range', '', $field);
  //                 [$startDate, $endDate] = $value;
  //                 if ($startDate && $endDate) {
  //                     $query->whereBetween($actualField, [$startDate, $endDate]);
  //                 }
  //             } elseif (is_array($value)) {
  //                 $query->whereIn($field, $value);
  //             } elseif (str_contains($field, '.')) {
  //                 [$relation, $relField] = explode('.', $field, 2);
  //                 $query->whereHas($relation, function ($sub) use ($relField, $value) {
  //                     $sub->where($relField, $value);
  //                 });
  //             } else {
  //                 $query->where($field, $value);
  //             }
  //         }
  //     }
  // }

  protected function applySearch(Builder $query)
  {
    if ($this->search === '' || empty($this->columns)) return;

    $search = $this->search;

    $query->where(function ($q) use ($search) {
      foreach ($this->columns as $col) {
        if (!empty($col['searchable'])) {
          $fields = [$col['field']];

          if (!empty($col['sub_fields'])) {
            foreach ($col['sub_fields'] as $sub) {
              $fields[] = $sub['field'];
            }
          }

          foreach ($fields as $field) {
            if ($this->isAccessorMethod($field)) continue;

            if (str_contains($field, '.')) {
              $parts = explode('.', $field);
              $column = array_pop($parts);
              $relations = $parts;

              $q->orWhereHas(
                implode('.', $relations),
                fn($sub) => $sub->where($column, 'like', "%{$search}%")
              );
            } else {
              $q->orWhere($field, 'like', "%{$search}%");
            }
          }
        }
      }
    });
  }


  protected function isAccessorMethod(string $field): bool
  {
    if (!class_exists($this->model)) return false;

    $modelInstance = new $this->model;

    // Cek accessor berbasis Attribute class
    $studly = \Illuminate\Support\Str::studly($field); // taken_date -> TakenDate
    if (method_exists($modelInstance, lcfirst($studly))) {
      return true;
    }

    // Cek accessor gaya lama getXxxAttribute
    $camel = \Illuminate\Support\Str::camel($field); // taken_date -> takenDate
    $method = 'get' . ucfirst($camel) . 'Attribute';
    return method_exists($modelInstance, $method);
  }
  // protected function isAccessorMethod(string $field): bool
  // {
  //     if (!class_exists($this->model)) return false;
  //
  //     $modelInstance = new $this->model;
  //     return method_exists($modelInstance, $field);
  // }

  public function updatedQueryParams()
  {
    $this->resetPage();
  }

  public function updatingSearch()
  {
    $this->resetPage();
  }

  public function sortBy(string $field)
  {
    if ($this->isAccessorMethod($field)) {
      return;
    }

    if ($this->sortField === $field) {
      $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      $this->sortField = $field;
      $this->sortDirection = 'asc';
    }

    $this->resetPage();
  }


  public function render(): View
  {
    if (!isset($this->model) || !class_exists($this->model)) {
      $message = isset($this->model)
        ? "Model class {$this->model} tidak ditemukan."
        : "Property \$model belum diset pada GenericTable.";
      throw new \Exception($message);
    }

    $query = ($this->model)::query();

    // Terapkan filter dasar & parameter
    $this->applyBaseConditions($query);
    $this->applyQueryParams($query);
    $this->applySearch($query);

    // Custom callback tambahan
    if ($this->customQueryCallback && is_callable($this->customQueryCallback)) {
      call_user_func($this->customQueryCallback, $query);
    }

    // Sorting (jika bukan accessor)
    if ($this->sortField && !$this->isAccessorMethod($this->sortField)) {
      $query->orderBy($this->sortField, $this->sortDirection);
    }

    /**
     * 🧠 Tambahan penting untuk hindari N+1 problem
     * Otomatis eager load semua relasi yang disebut di kolom
     * Contoh: field 'user.name' => eager load 'user'
     *         field 'team.coach.name' => eager load 'team.coach'
     */
    $relations = collect($this->columns)
      ->pluck('field')
      ->merge(
        collect($this->columns)
          ->pluck('sub_fields')
          ->filter()
          ->flatten(1)
          ->pluck('field')
      )
      ->filter(fn($f) => str_contains($f, '.'))
      ->map(fn($f) => implode('.', array_slice(explode('.', $f), 0, -1)))
      ->unique()
      ->values()
      ->all();

    if (!empty($relations)) {
      $query->with($relations);
    }

    // Pagination
    $rows = $query->paginate($this->perPage);

    // Sorting manual jika sortField adalah accessor
    if ($this->sortField && $this->isAccessorMethod($this->sortField)) {
      $sortedItems = $rows->getCollection()->sortBy(
        fn($item) => $item->{$this->sortField},
        SORT_REGULAR,
        $this->sortDirection === 'desc'
      );

      $rows->setCollection($sortedItems->values());
    }

    return view('livewire-generic-table::livewire.generic-table', [
      'rows' => $this->rows,
      'columns' => $this->columns,
      'model' => $this->model,
    ]);
  }

  #[On('refreshTable')]
  public function refreshTable()
  {
    $this->render();
  }
}
