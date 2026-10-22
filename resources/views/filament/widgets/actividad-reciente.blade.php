<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Actividad reciente</x-slot>

        @php
            $actividades = $this->getActividades();
        @endphp

        @if ($actividades->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center">
                <p class="text-sm font-medium text-gray-700">Sin actividad reciente</p>
                <p class="mt-1 text-xs text-gray-500">Los cambios del despacho aparecerán aquí.</p>
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($actividades as $item)
                    @php
                        /** @var \App\Models\Activity $activity */
                        $activity = $item['activity'];
                    @endphp
                    <li class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                            <x-filament::icon :icon="$item['icon']" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $item['label'] }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $activity->description }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $activity->created_at?->timezone('America/Costa_Rica')->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
