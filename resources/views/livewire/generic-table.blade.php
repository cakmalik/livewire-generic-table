<div class="space-y-4">

    <!-- Search & Per Page -->
    <div class="flex items-center justify-between gap-2">
        @if ($filterPerPage)
            <flux:select wire:model.live="perPage" size="sm" class="max-w-1/3 sm:max-w-20">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </flux:select>
        @else
            <div></div>
        @endif

        <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="Search..." size="sm"
            class="w-auto sm:max-w-xs">
            @if (!empty($search))
                <x-slot name="iconTrailing">
                    <flux:button size="sm" @click="$wire.set('search', '')" variant="subtle" icon="x-mark"
                        class="-mr-1" />
                </x-slot>
            @endif
        </flux:input>


    </div>

    <!-- Table -->
    <div class="overflow-x-auto shadow rounded-lg relative">

        <x-loading-table class="absolute" wire:loading />

        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700  rounded-lg relative">

            <thead class="bg-primary-400 rounded-lg">
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col"
                            class="px-4 py-3 text-xs font-semibold text-center text-white uppercase tracking-wider cursor-pointer"
                            @if (!empty($column['sortable'])) wire:click.prevent="sortBy('{{ $column['field'] ?? '' }}')" @endif>
                            <div class="flex items-center gap-2 text-center">
                                <span>{{ $column['label'] ?? ucfirst($column['field'] ?? '') }}</span>
                                @if (!empty($column['sortable']) && $sortField === ($column['field'] ?? ''))
                                    <span class="text-xs">
                                        ({{ $sortDirection }})
                                    </span>
                                @endif
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700"
                        @click="$dispatch('rowAction', {id:'{{ $row->id }}'})">
                        @foreach ($columns as $column)
                            @php
                                $field = $column['field'] ?? null;
                                $value = data_get($row, $field);
                                $alignClass = match ($column['align'] ?? 'left') {
                                    'right' => 'text-right',
                                    'center' => 'text-center',
                                    default => 'text-left',
                                };
                            @endphp
                            @php
                                $width = $column['width'] ?? '200px';
                            @endphp

                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200 {{ $alignClass }} truncate"
                                style="max-width: {{ $width }}">
                                {{-- 🔹 Actions --}}
                                @if (!empty($column['sub_fields']))
                                    <div class="flex flex-col space-y-1">
                                        {{-- Main field --}}
                                        <div>{{ data_get($row, $field) }}</div>

                                        {{-- Sub fields --}}
                                        @foreach ($column['sub_fields'] as $subField)
                                            <div class="{{ $subField['class'] ?? 'text-xs text-gray-500' }}">
                                                {{ ($subField['prefix'] ?? '') . data_get($row, $subField['field']) }}
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif (!empty($column['actions']))
                                    <div class="flex gap-2 items-center">
                                        @foreach ($column['actions'] as $action)
                                            @php
                                                $showAction = true;

                                                // Jika ada field 'condition_field' dan 'condition_value'
                                                if (isset($action['condition_field'], $action['condition_value'])) {
                                                    $showAction =
                                                        data_get($row, $action['condition_field']) ==
                                                        $action['condition_value'];
                                                }

                                                $event = $action['event'] ?? null;

                                                // ✔ Kondisi edit
                                                if ($showAction && $event === 'editAction') {
                                                    $showAction =
                                                        !$permissionPrefix ||
                                                        auth()
                                                            ->user()
                                                            ?->can($permissionPrefix . '.update');
                                                }

                                                // ✔ Kondisi show
                                                if ($showAction && $event === 'showAction') {
                                                    $showAction =
                                                        !$permissionPrefix ||
                                                        auth()
                                                            ->user()
                                                            ?->can($permissionPrefix . '.view');
                                                }

                                                // ✔ Kondisi delete
                                                if ($showAction && $event === 'deleteAction') {
                                                    $showAction =
                                                        !$permissionPrefix ||
                                                        auth()
                                                            ->user()
                                                            ?->can($permissionPrefix . '.delete');
                                                }
                                            @endphp

                                            @if (!$showAction)
                                                @continue
                                            @endif

                                            {{-- RENDER ACTION BUTTON --}}
                                            @if (($action['type'] ?? '') === 'text')
                                                <button
                                                    @click.stop="$dispatch('{{ $action['event'] ?? '' }}', { id: {{ $row->id }} })"
                                                    class="text-{{ $action['color'] ?? 'gray' }}-600 dark:text-{{ $action['color'] ?? 'gray' }}-400 text-sm hover:underline flex items-center gap-1">
                                                    @if (!empty($action['icon']))
                                                        <x-heroicon-o-{{ $action['icon'] }} class="w-4 h-4" />
                                                    @endif
                                                    {{ $action['label'] ?? '' }}
                                                </button>
                                            @elseif (($action['type'] ?? '') === 'flux')
                                                <flux:button size="sm" icon="{{ $action['icon'] ?? '' }}"
                                                    @click.stop="$dispatch('{{ $action['event'] ?? '' }}', {id: '{{ $row->id }}'})"
                                                    color="{{ $action['color'] ?? 'indigo' }}"
                                                    variant="{{ $action['variant'] ?? 'ghost' }}">
                                                    {{ $action['label'] ?? '' }}
                                                </flux:button>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- 🔹 Format predefined --}}
                                @elseif (!empty($column['format']))
                                    @switch($column['format'])
                                        @case('image')
                                            @php
                                                $imgUrl = $value
                                                    ? (Str::startsWith($value, ['http://', 'https://'])
                                                        ? $value
                                                        : asset($value))
                                                    : $column['default'] ?? asset('images/no-images.jpg');
                                                $width = $column['width'] ?? 60;
                                                $height = $column['height'] ?? 60;
                                                $rounded = $column['rounded'] ?? false;
                                            @endphp

                                            <img src="{{ $imgUrl }}" alt="Image" width="{{ $width }}"
                                                height="{{ $height }}"
                                                class="{{ $rounded ? 'rounded-full object-cover' : 'object-cover' }}" />
                                        @break

                                        @case('avatar')
                                            <flux:avatar circle src="{{ $value }}" alt="Avatar" />
                                        @break

                                        @case('image')
                                            <img src="{{ $value }}" alt="Image"
                                                class="w-10 h-10 rounded-full object-cover" />
                                        @break

                                        @case('custom')
                                            @if (!empty($column['custom_method']) && method_exists($this, $column['custom_method']))
                                                {!! $this->{$column['custom_method']}($row, $value) !!}
                                            @endif
                                        @break

                                        @case('badge')
                                            @php
                                                $badgeCfg = $column['badge'] ?? [];

                                                // Default styling
                                                $baseClass =
                                                    $badgeCfg['class'] ??
                                                    'px-2 py-1 rounded-full text-xs font-semibold';

                                                // Nilai default jika tidak ditemukan
                                                $defaultColor = $badgeCfg['default'] ?? 'gray';
                                                $defaultLabel =
                                                    $badgeCfg['default_label'] ??
                                                    strtoupper(
                                                        $value instanceof \BackedEnum ? $value->value : (string) $value,
                                                    );

                                                // Variabel awal
                                                $color = $defaultColor;
                                                $label = $defaultLabel;

                                                // 🧩 Kondisi 1: Jika $value adalah Enum
                                                if ($value instanceof \BackedEnum) {
                                                    $color = method_exists($value, 'color')
                                                        ? $value->color()
                                                        : $defaultColor;
                                                    $label = method_exists($value, 'label')
                                                        ? $value->label()
                                                        : $value->value;
                                                }
                                                // 🧩 Kondisi 2: Jika pakai konfigurasi array (bukan enum)
                                                else {
                                                    $colors = $badgeCfg['colors'] ?? [];
                                                    $labels = $badgeCfg['labels'] ?? [];

                                                    $colorClass = $colors[$value] ?? null;
                                                    $labelText = $labels[$value] ?? null;

                                                    // Jika ada di config array, override default
                                                    if ($colorClass) {
                                                        // Misal value dari config sudah bentuk "bg-green-100 text-green-800"
                                                        $color = $colorClass;
                                                        $label = $labelText ?? $label;
                                                    } else {
                                                        // Kalau tidak ada di config array, fallback ke default
                                                        $color = "bg-{$defaultColor}-100 text-{$defaultColor}-800";
                                                    }
                                                }

                                                // 🧩 Jika enum, color akan berupa nama warna (green/red/etc)
                                                //     maka ubah jadi tailwind class
                                                if (!str_starts_with($color, 'bg-')) {
                                                    $color = "bg-{$color}-100 text-{$color}-800";
                                                }
                                            @endphp

                                            <span class="{{ $baseClass }} {{ $color }}">
                                                {{ $label }}
                                            </span>
                                        @break

                                        @case('boolean-label')
                                            <span
                                                class="px-2 py-1 text-xs rounded
                                                {{ $value ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $value ? 'Active' : 'Inactive' }}
                                            </span>
                                        @break

                                        @case('uppercase')
                                            {{ strtoupper($value) }}
                                        @break

                                        @case('currency')
                                            Rp {{ number_format($value, 0, ',', '.') }}
                                        @break

                                        @case('number')
                                            {{ number_format($value) }}
                                        @break

                                        @case('date')
                                            @if (!empty($value))
                                                {{ \Carbon\Carbon::parse($value)->format($column['format_date'] ?? 'd/m/Y') }}
                                            @endif
                                        @break

                                        @case('datetime')
                                            @if (!empty($value))
                                                {{ \Carbon\Carbon::parse($value)->format($column['format_date'] ?? 'd/m/Y H:i') }}
                                            @endif
                                        @break

                                        @default
                                            {{ $value }}
                                    @endswitch

                                    {{-- 🔹 Field relasi dot notation --}}
                                @elseif (!empty($field) && str_contains($field, '.'))
                                    {{ data_get($row, $field) }}

                                    {{-- 🔹 Field biasa --}}
                                    {{-- @else --}}
                                    {{--     {{ $value }} --}}
                                    {{-- @endif --}}
                                @else
                                    {{ $value === null ? '-' : (is_scalar($value) ? $value : json_encode($value)) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}"
                                class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-300">
                                <x-aplus.state-empty :is_animate="false" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
