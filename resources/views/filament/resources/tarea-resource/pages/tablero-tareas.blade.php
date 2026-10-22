<x-filament-panels::page>
    @php
        $columnas = $this->getColumnas();
        $estados = \App\Enums\EstadoTarea::columnasTablero();
        $columnaActiva = \App\Enums\EstadoTarea::tryFrom($this->columnaActiva)
            ?? \App\Enums\EstadoTarea::Pendiente;
    @endphp

    <div class="kanban-toolbar space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                <x-filament::button
                    size="sm"
                    class="!min-h-11 w-full sm:w-auto"
                    :color="$filtroAsignada === 'todas' ? 'primary' : 'gray'"
                    wire:click="$set('filtroAsignada', 'todas')"
                >
                    Todas
                </x-filament::button>
                <x-filament::button
                    size="sm"
                    class="!min-h-11 w-full sm:w-auto"
                    :color="$filtroAsignada === 'mias' ? 'primary' : 'gray'"
                    wire:click="$set('filtroAsignada', 'mias')"
                >
                    Mis tareas
                </x-filament::button>
            </div>
            <p class="hidden text-sm text-gray-500 md:block">
                Arrastrá las tarjetas entre columnas, como en Trello.
            </p>
            <p class="text-sm text-gray-500 md:hidden">
                Cambiá de columna con las pestañas. Podés arrastrar o usar «Mover».
            </p>
        </div>

        {{-- Pestañas solo en móvil --}}
        <div
            class="kanban-tabs md:hidden"
            role="tablist"
            aria-label="Columnas del tablero"
        >
            @foreach ($estados as $estado)
                @php
                    $count = ($columnas[$estado->value] ?? collect())->count();
                    $activa = $columnaActiva === $estado;
                @endphp
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('columnaActiva', '{{ $estado->value }}')"
                    aria-selected="{{ $activa ? 'true' : 'false' }}"
                    @class([
                        'kanban-tab',
                        'kanban-tab-active' => $activa,
                    ])
                >
                    <span @class([
                        'kanban-tab-dot',
                        'bg-gray-400' => $estado === \App\Enums\EstadoTarea::Pendiente,
                        'bg-sky-500' => $estado === \App\Enums\EstadoTarea::EnProgreso,
                        'bg-emerald-500' => $estado === \App\Enums\EstadoTarea::Completada,
                    ])></span>
                    <span class="kanban-tab-label">{{ $estado->getLabel() }}</span>
                    <span class="kanban-tab-count">{{ $count }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div
        class="kanban-board mt-4 grid grid-cols-1 gap-4 md:grid-cols-3"
        wire:ignore.self
        x-data="tareaKanban(@this)"
        x-init="init()"
    >
        @foreach ($estados as $estado)
            @php
                $tareas = $columnas[$estado->value] ?? collect();
                $visibleMovil = $columnaActiva === $estado;
            @endphp
            <section
                @class([
                    'kanban-column flex min-h-[20rem] flex-col rounded-xl border border-gray-200 bg-slate-100/80 p-3 dark:border-gray-700 dark:bg-gray-900/40 md:min-h-[28rem]',
                    'hidden md:flex' => ! $visibleMovil,
                    'flex' => $visibleMovil,
                ])
                data-estado="{{ $estado->value }}"
            >
                <header class="mb-3 hidden items-center justify-between gap-2 px-1 md:flex">
                    <div class="flex items-center gap-2">
                        <span @class([
                            'h-2.5 w-2.5 rounded-full',
                            'bg-gray-400' => $estado === \App\Enums\EstadoTarea::Pendiente,
                            'bg-sky-500' => $estado === \App\Enums\EstadoTarea::EnProgreso,
                            'bg-emerald-500' => $estado === \App\Enums\EstadoTarea::Completada,
                        ])></span>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $estado->getLabel() }}
                        </h2>
                    </div>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                        {{ $tareas->count() }}
                    </span>
                </header>

                <header class="mb-3 flex items-center justify-between px-1 md:hidden">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                        {{ $estado->getLabel() }}
                    </h2>
                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                        {{ $tareas->count() }} {{ $tareas->count() === 1 ? 'tarea' : 'tareas' }}
                    </span>
                </header>

                <ul
                    class="kanban-list flex flex-1 flex-col gap-2"
                    data-estado="{{ $estado->value }}"
                >
                    @forelse ($tareas as $tarea)
                        <li
                            wire:key="tarea-{{ $tarea->id }}"
                            data-id="{{ $tarea->id }}"
                            class="kanban-card group cursor-grab rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:shadow-md active:cursor-grabbing dark:border-gray-700 dark:bg-gray-800 {{ $tarea->estaVencida() ? 'ring-2 ring-danger-300' : '' }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="min-w-0 flex-1 text-sm font-medium leading-snug text-gray-900 dark:text-gray-100">
                                    {{ $tarea->descripcion }}
                                </p>
                                <div class="kanban-card-actions flex shrink-0 items-center gap-1">
                                    <a
                                        href="{{ \App\Filament\Resources\TareaResource::getUrl('edit', ['record' => $tarea]) }}"
                                        class="kanban-icon-btn"
                                        title="Editar"
                                        aria-label="Editar tarea"
                                    >
                                        <x-heroicon-o-pencil-square class="h-5 w-5" />
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="eliminarTarea({{ $tarea->id }})"
                                        wire:confirm="¿Eliminar esta tarea?"
                                        class="kanban-icon-btn kanban-icon-btn-danger"
                                        title="Eliminar"
                                        aria-label="Eliminar tarea"
                                    >
                                        <x-heroicon-o-trash class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <x-filament::badge :color="$this->colorPrioridad($tarea->prioridad)" size="sm">
                                    {{ $tarea->prioridad->getLabel() }}
                                </x-filament::badge>

                                @if ($tarea->asignada)
                                    <span class="inline-flex max-w-full items-center gap-1 truncate rounded-full bg-slate-100 px-2 py-0.5 dark:bg-gray-700">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-600 text-[10px] font-semibold text-white">
                                            {{ strtoupper(mb_substr($tarea->asignada->name, 0, 1)) }}
                                        </span>
                                        <span class="truncate">{{ $tarea->asignada->name }}</span>
                                    </span>
                                @endif

                                @if ($tarea->vence_el)
                                    <span @class([
                                        'inline-flex items-center gap-1',
                                        'font-semibold text-danger-600' => $tarea->estaVencida(),
                                    ])>
                                        <x-heroicon-o-clock class="h-3.5 w-3.5" />
                                        {{ $tarea->vence_el->format('d M') }}
                                    </span>
                                @endif

                                @if ($tarea->expediente)
                                    <span class="truncate text-gray-400">
                                        {{ $tarea->expediente->numero_interno }}
                                    </span>
                                @endif
                            </div>

                            {{-- Mover sin drag: útil en celular --}}
                            <div class="kanban-move mt-3 border-t border-gray-100 pt-3 dark:border-gray-700 md:hidden">
                                <label class="sr-only" for="mover-{{ $tarea->id }}">Mover a columna</label>
                                <select
                                    id="mover-{{ $tarea->id }}"
                                    class="kanban-move-select"
                                    wire:change="moverTarea({{ $tarea->id }}, $event.target.value)"
                                >
                                    @foreach ($estados as $destino)
                                        <option
                                            value="{{ $destino->value }}"
                                            @selected($destino === $tarea->estado)
                                        >
                                            Mover a: {{ $destino->getLabel() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </li>
                    @empty
                        <li class="kanban-empty pointer-events-none rounded-xl border border-dashed border-gray-300 px-3 py-10 text-center text-sm text-gray-400 dark:border-gray-600">
                            <span class="md:hidden">No hay tareas en esta columna</span>
                            <span class="hidden md:inline">Soltá tareas acá</span>
                        </li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>

    @assets
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js?v={{ asset_version() }}"></script>
    @endassets

    @script
        <script>
            Alpine.data('tareaKanban', (component) => ({
                sortables: [],

                init() {
                    this.$nextTick(() => this.mountSortables());

                    Livewire.hook('morph.updated', ({ el }) => {
                        if (el === this.$el || this.$el.contains(el)) {
                            this.destroySortables();
                            this.$nextTick(() => this.mountSortables());
                        }
                    });
                },

                destroySortables() {
                    this.sortables.forEach((sortable) => sortable.destroy());
                    this.sortables = [];
                },

                isMobile() {
                    return window.matchMedia('(max-width: 767px)').matches;
                },

                mountSortables() {
                    if (typeof Sortable === 'undefined') {
                        return;
                    }

                    this.$el.querySelectorAll('.kanban-list').forEach((list) => {
                        const column = list.closest('.kanban-column');
                        if (column && column.classList.contains('hidden')) {
                            return;
                        }

                        const sortable = Sortable.create(list, {
                            group: 'tareas',
                            animation: 180,
                            draggable: '.kanban-card',
                            handle: this.isMobile() ? null : undefined,
                            ghostClass: 'kanban-ghost',
                            chosenClass: 'kanban-chosen',
                            dragClass: 'kanban-drag',
                            emptyInsertThreshold: 24,
                            delay: this.isMobile() ? 180 : 0,
                            delayOnTouchOnly: true,
                            touchStartThreshold: 6,
                            onAdd: (evt) => this.persist(evt),
                            onUpdate: (evt) => this.persist(evt),
                        });

                        this.sortables.push(sortable);
                    });
                },

                persist(evt) {
                    const list = evt.to;
                    const estado = list.dataset.estado;
                    const card = evt.item;
                    const tareaId = Number(card.dataset.id);
                    const ordenIds = Array.from(list.querySelectorAll('.kanban-card'))
                        .map((el) => Number(el.dataset.id));

                    const empty = list.querySelector('.kanban-empty');
                    if (empty) {
                        empty.remove();
                    }

                    component.call('moverTarea', tareaId, estado, ordenIds);
                },
            }));
        </script>
    @endscript
</x-filament-panels::page>
