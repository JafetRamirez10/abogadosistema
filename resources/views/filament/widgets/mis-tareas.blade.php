<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Mis tareas</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ $this->verTodasUrl() }}" class="text-sm font-medium text-primary-600 hover:underline">
                Ver todas
            </a>
        </x-slot>

        @php
            $tareas = $this->getTareas();
        @endphp

        @if ($tareas->isEmpty())
            <p class="text-sm text-gray-500">No tenés tareas pendientes.</p>
        @else
            <ul class="space-y-2">
                @foreach ($tareas as $tarea)
                    @php
                        $vencida = $this->esVencida($tarea);
                        $color = $this->colorPrioridad($tarea->prioridad);
                        $itemClass = $vencida
                            ? 'flex items-start gap-3 rounded-xl border p-3 border-danger-300 bg-danger-50'
                            : 'flex items-start gap-3 rounded-xl border p-3 border-gray-200 bg-white';
                        $titleClass = $vencida
                            ? 'text-sm font-medium text-danger-700'
                            : 'text-sm font-medium text-gray-900';
                        $venceClass = $vencida
                            ? 'font-semibold text-danger-600'
                            : '';
                    @endphp
                    <li class="{{ $itemClass }}">
                        <input
                            type="checkbox"
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            wire:click="completar({{ $tarea->id }})"
                            wire:loading.attr="disabled"
                            aria-label="Completar tarea"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="{{ $titleClass }}">
                                {{ $tarea->titulo }}
                            </p>
                            @if (filled($tarea->descripcion))
                                <p class="mt-0.5 line-clamp-2 text-xs text-gray-500">
                                    {{ $tarea->descripcion }}
                                </p>
                            @endif
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <x-filament::badge :color="$color" size="sm">
                                    {{ $tarea->prioridad->getLabel() }}
                                </x-filament::badge>
                                @if ($tarea->vence_el)
                                    <span class="{{ $venceClass }}">
                                        Vence {{ $tarea->vence_el->format('d/m/Y') }}
                                    </span>
                                @endif
                                @if ($tarea->expediente)
                                    <span>{{ $tarea->expediente->numero_interno }}</span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
