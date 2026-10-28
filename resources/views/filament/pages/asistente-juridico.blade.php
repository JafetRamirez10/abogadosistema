<x-filament-panels::page>
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
        Asistente con acceso al despacho: puede <strong>buscar y crear</strong> expedientes/tareas.
        Para <strong>eliminar o actualizar</strong> siempre pide confirmación explícita.
        Proveedor: <strong>{{ $this->proveedorActivo() }}</strong>. Corpus legal demo (no oficial).
    </div>

    @if (count($this->pendientes) > 0)
        <div class="mb-4 space-y-3 rounded-xl border border-danger-300 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-950/40">
            <p class="text-sm font-semibold text-danger-800 dark:text-danger-100">Confirmación requerida</p>
            @foreach ($this->pendientes as $pendiente)
                <div class="rounded-lg border border-danger-200 bg-white p-3 text-sm dark:border-danger-900 dark:bg-gray-900">
                    <p class="font-medium text-danger-700 dark:text-danger-200">
                        {{ strtoupper($pendiente['tipo']) }}
                    </p>
                    <p class="mt-1 whitespace-pre-wrap text-gray-800 dark:text-gray-100">{{ $pendiente['resumen'] }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-filament::button
                            color="danger"
                            size="sm"
                            wire:click="confirmarPendiente({{ Js::from($pendiente['id']) }})"
                            wire:confirm="¿Confirmás esta acción? Se aplicará en la base de datos."
                        >
                            Confirmar
                        </x-filament::button>
                        <x-filament::button
                            color="gray"
                            size="sm"
                            wire:click="cancelarPendiente({{ Js::from($pendiente['id']) }})"
                        >
                            Cancelar
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3 space-y-4">
            <div class="min-h-[22rem] space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                @forelse ($this->mensajes as $mensaje)
                    <div @class([
                        'rounded-lg px-3 py-2 text-sm whitespace-pre-wrap',
                        'bg-primary-50 text-primary-950 dark:bg-primary-950/30 dark:text-primary-100' => $mensaje['role'] === 'user',
                        'bg-gray-50 text-gray-900 dark:bg-gray-800 dark:text-gray-100' => $mensaje['role'] !== 'user',
                    ])>
                        <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide opacity-70">
                            {{ $mensaje['role'] === 'user' ? 'Vos' : 'Asistente' }}
                        </div>
                        {{ $mensaje['content'] }}

                        @if (! empty($mensaje['acciones']))
                            <div class="mt-2 text-xs text-success-700 dark:text-success-300">
                                <p class="font-semibold">Acciones ejecutadas</p>
                                <ul class="list-disc pl-4">
                                    @foreach ($mensaje['acciones'] as $accion)
                                        <li>{{ $accion }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (! empty($mensaje['fuentes']))
                            <div class="mt-3 border-t border-gray-200 pt-2 dark:border-gray-700">
                                <p class="mb-1 text-[11px] font-semibold uppercase text-gray-500">Fuentes recuperadas</p>
                                <ul class="space-y-2">
                                    @foreach ($mensaje['fuentes'] as $fuente)
                                        <li class="rounded-md bg-white/70 px-2 py-1.5 text-xs dark:bg-gray-900/50">
                                            <strong>{{ $fuente['fuente'] }}</strong>
                                            @if ($fuente['referencia'])
                                                — {{ $fuente['referencia'] }}
                                            @endif
                                            <div class="mt-0.5 text-gray-500">{{ $fuente['extracto'] }}…</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">
                        Ejemplos: «Creá una tarea Revisar poder especial», «Buscá expedientes de traspaso»,
                        «Eliminá la tarea 12» (pedirá confirmación).
                    </p>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-2 space-y-3">
            <form wire:submit="enviar" class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                {{ $this->form }}

                <div class="flex flex-wrap gap-2">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        Enviar
                    </x-filament::button>
                    <x-filament::button color="gray" type="button" wire:click="limpiarChat">
                        Limpiar chat
                    </x-filament::button>
                </div>

                <p wire:loading wire:target="enviar" class="text-xs text-gray-500">
                    Procesando con el modelo y herramientas…
                </p>
            </form>
        </div>
    </div>
</x-filament-panels::page>
