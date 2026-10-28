<x-filament-panels::page>
    @php
        $mora = $this->cuotasEnMora();
        $medidas = $this->medidasPorRevisar();
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
            Reportes operativos del despacho. Integraciones con el Poder Judicial y facturación DGT siguen fuera de alcance en esta versión.
        </div>

        <x-filament::section>
            <x-slot name="heading">Mora alimentaria</x-slot>
            <x-slot name="description">Cuotas vencidas con saldo pendiente (hasta 50).</x-slot>

            @if ($mora->isEmpty())
                <p class="text-sm text-gray-500">No hay cuotas alimentarias en mora.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[40rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-700">
                                <th class="px-2 py-2">Expediente</th>
                                <th class="px-2 py-2">Concepto</th>
                                <th class="px-2 py-2">Periodo</th>
                                <th class="px-2 py-2">Venció</th>
                                <th class="px-2 py-2">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mora as $cuota)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-2 py-2">
                                        <a
                                            href="{{ \App\Filament\Resources\ExpedienteResource::getUrl('view', ['record' => $cuota->expediente_id]) }}"
                                            class="font-medium text-primary-600 hover:underline"
                                        >
                                            {{ $cuota->expediente?->numero_interno ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="px-2 py-2">{{ $cuota->concepto->getLabel() }}</td>
                                    <td class="px-2 py-2">{{ $cuota->periodo ?? '—' }}</td>
                                    <td class="px-2 py-2 text-danger-600">{{ $cuota->vence_el->format('d/m/Y') }}</td>
                                    <td class="px-2 py-2 font-semibold">
                                        {{ formatear_monto($cuota->saldo, $cuota->moneda) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Medidas cautelares por revisar</x-slot>
            <x-slot name="description">Vigentes con revisión o vencimiento en los próximos 7 días (hasta 50).</x-slot>

            @if ($medidas->isEmpty())
                <p class="text-sm text-gray-500">No hay medidas cautelares próximas a revisar o vencer.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[40rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-700">
                                <th class="px-2 py-2">Expediente</th>
                                <th class="px-2 py-2">Tipo</th>
                                <th class="px-2 py-2">Revisión</th>
                                <th class="px-2 py-2">Vence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($medidas as $medida)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-2 py-2">
                                        <a
                                            href="{{ \App\Filament\Resources\ExpedienteResource::getUrl('view', ['record' => $medida->expediente_id]) }}"
                                            class="font-medium text-primary-600 hover:underline"
                                        >
                                            {{ $medida->expediente?->numero_interno ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="px-2 py-2">{{ $medida->tipo->getLabel() }}</td>
                                    <td class="px-2 py-2">{{ $medida->fecha_revision?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-2 py-2">{{ $medida->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
