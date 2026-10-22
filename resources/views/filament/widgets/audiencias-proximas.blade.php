<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Audiencias próximas</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ $this->calendarioUrl() }}" class="text-sm font-medium text-primary-600 hover:underline">
                Ver calendario
            </a>
        </x-slot>

        @php
            $audiencias = $this->getAudiencias();
        @endphp

        @if ($audiencias->isEmpty())
            <p class="text-sm text-gray-500">No hay audiencias próximas.</p>
        @else
            <ul class="space-y-3">
                @foreach ($audiencias as $item)
                    @php
                        $evento = $item['evento'];
                        $inicio = $evento->inicio->timezone('America/Costa_Rica');
                        $cliente = $evento->expediente?->cliente?->nombre;
                    @endphp
                    <li class="flex gap-3 rounded-xl border border-gray-200 bg-white p-3">
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg bg-primary-50 text-primary-700">
                            <span class="text-[10px] font-semibold uppercase tracking-wide">{{ $item['etiqueta_fecha'] }}</span>
                            <span class="text-xs font-bold">{{ $inicio->format('H:i') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $evento->titulo }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $cliente ?: 'Sin cliente' }}</p>
                            <p class="truncate text-xs text-gray-400">{{ $evento->lugar ?: 'Sin lugar' }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
