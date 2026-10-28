<x-filament-panels::page>
    <div
        class="flex h-[min(78vh,52rem)] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
        x-data
        x-on:chat-scrolled.window="$nextTick(() => { const el = $refs.chatScroll; if (el) el.scrollTop = el.scrollHeight })"
    >
        {{-- Cabecera tipo chat --}}
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">Asistente jurídico</p>
                <p class="truncate text-xs text-gray-500">
                    {{ $this->proveedorActivo() }}
                    · puede crear/buscar; borrar o editar pide confirmación
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-1.5">
                <x-filament::button
                    color="gray"
                    size="sm"
                    type="button"
                    wire:click="$toggle('mostrandoOpciones')"
                >
                    Opciones
                </x-filament::button>
                <x-filament::button
                    color="gray"
                    size="sm"
                    type="button"
                    wire:click="limpiarChat"
                    wire:confirm="¿Limpiar toda la conversación?"
                >
                    Limpiar
                </x-filament::button>
            </div>
        </div>

        @if ($mostrandoOpciones)
            <div class="shrink-0 space-y-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/60">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Especialidad</label>
                        <select
                            wire:model.live="especialidad"
                            class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900"
                        >
                            <option value="general">General</option>
                            <option value="familia">Familia</option>
                            <option value="notarial">Notarial / inmobiliario</option>
                            <option value="penal">Penal</option>
                        </select>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input type="checkbox" wire:model.live="usarExpediente" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            Incluir expediente en el contexto
                        </label>
                    </div>
                </div>
                @if ($usarExpediente)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Expediente</label>
                        <select
                            wire:model.live="expedienteId"
                            class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900"
                        >
                            <option value="">Elegí un expediente…</option>
                            @foreach ($this->expedientesOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif

        @if (count($this->pendientes) > 0)
            <div class="shrink-0 space-y-2 border-b border-danger-200 bg-danger-50 px-4 py-3 dark:border-danger-900 dark:bg-danger-950/40">
                <p class="text-xs font-semibold uppercase tracking-wide text-danger-700 dark:text-danger-200">Confirmación requerida</p>
                @foreach ($this->pendientes as $pendiente)
                    <div class="rounded-xl border border-danger-200 bg-white p-3 text-sm dark:border-danger-800 dark:bg-gray-900">
                        <p class="font-medium text-danger-700 dark:text-danger-200">{{ strtoupper($pendiente['tipo']) }}</p>
                        <p class="mt-1 whitespace-pre-wrap text-gray-800 dark:text-gray-100">{{ $pendiente['resumen'] }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-filament::button
                                color="danger"
                                size="sm"
                                wire:click="confirmarPendiente({{ Js::from($pendiente['id']) }})"
                                wire:confirm="¿Confirmás? Se aplicará en la base de datos."
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

        {{-- Hilo de mensajes --}}
        <div
            x-ref="chatScroll"
            class="fi-chat-scroll min-h-0 flex-1 space-y-3 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-3 py-4 sm:px-5 dark:from-gray-950 dark:to-gray-900"
            wire:ignore.self
        >
            @forelse ($this->mensajes as $mensaje)
                @php $esUsuario = ($mensaje['role'] ?? '') === 'user'; @endphp
                <div @class(['flex', 'justify-end' => $esUsuario, 'justify-start' => ! $esUsuario])>
                    <div @class([
                        'max-w-[min(100%,36rem)] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm',
                        'rounded-br-md bg-primary-600 text-white' => $esUsuario,
                        'rounded-bl-md border border-gray-200 bg-white text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100' => ! $esUsuario,
                    ])>
                        <div @class([
                            'mb-1 text-[10px] font-semibold uppercase tracking-wide',
                            'text-primary-100' => $esUsuario,
                            'text-gray-400' => ! $esUsuario,
                        ])>
                            {{ $esUsuario ? 'Vos' : 'Asistente' }}
                        </div>
                        <div class="whitespace-pre-wrap break-words leading-relaxed">{{ $mensaje['content'] }}</div>

                        @if (! empty($mensaje['acciones']))
                            <div @class(['mt-2 border-t pt-2 text-xs', 'border-primary-500/40 text-primary-50' => $esUsuario, 'border-gray-200 text-success-700 dark:border-gray-600 dark:text-success-300' => ! $esUsuario])>
                                <p class="font-semibold">Acciones</p>
                                <ul class="mt-0.5 list-disc pl-4">
                                    @foreach ($mensaje['acciones'] as $accion)
                                        <li>{{ $accion }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (! empty($mensaje['fuentes']))
                            <details class="mt-2 border-t border-gray-200 pt-2 text-xs dark:border-gray-600">
                                <summary class="cursor-pointer font-semibold text-gray-500">Fuentes ({{ count($mensaje['fuentes']) }})</summary>
                                <ul class="mt-2 space-y-2">
                                    @foreach ($mensaje['fuentes'] as $fuente)
                                        <li class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-gray-900/60">
                                            <strong>{{ $fuente['fuente'] }}</strong>
                                            @if ($fuente['referencia']) — {{ $fuente['referencia'] }} @endif
                                            <div class="mt-0.5 text-gray-500">{{ $fuente['extracto'] }}…</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center px-4 py-10 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                        <x-heroicon-o-sparkles class="h-6 w-6" />
                    </div>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">¿En qué te ayudo?</p>
                    <p class="mt-1 max-w-md text-sm text-gray-500">
                        Escribí como en un chat: consultas legales, crear tareas o buscar expedientes.
                    </p>
                    <div class="mt-5 flex max-w-lg flex-wrap justify-center gap-2">
                        @foreach ([
                            'Creá una tarea: Revisar escritura de traspaso',
                            'Buscá expedientes de pensión alimentaria',
                            '¿Qué revisar en un poder notarial especial?',
                        ] as $sugerencia)
                            <button
                                type="button"
                                wire:click="usarSugerencia({{ Js::from($sugerencia) }})"
                                class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-left text-xs text-gray-700 shadow-sm transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-500"
                            >
                                {{ $sugerencia }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforelse

            @if ($escribiendo)
                <div class="flex justify-start">
                    <div class="rounded-2xl rounded-bl-md border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <span class="inline-flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Escribiendo…
                        </span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Composer --}}
        <form
            wire:submit="enviar"
            class="shrink-0 border-t border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900"
        >
            <div class="flex items-end gap-2 rounded-2xl border border-gray-200 bg-gray-50 p-2 shadow-inner dark:border-gray-600 dark:bg-gray-800/80">
                <textarea
                    wire:model="mensaje"
                    rows="1"
                    maxlength="4000"
                    placeholder="Escribí un mensaje…"
                    class="max-h-36 min-h-[2.75rem] flex-1 resize-y border-0 bg-transparent px-2 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0 dark:text-gray-100"
                    x-on:keydown.enter.exact.prevent="if (!$wire.escribiendo) $wire.enviar()"
                    @if ($escribiendo) disabled @endif
                ></textarea>
                <x-filament::button
                    type="submit"
                    size="sm"
                    class="shrink-0"
                    wire:loading.attr="disabled"
                    wire:target="enviar"
                >
                    <span wire:loading.remove wire:target="enviar">Enviar</span>
                    <span wire:loading wire:target="enviar">…</span>
                </x-filament::button>
            </div>
            <p class="mt-1.5 px-1 text-[11px] text-gray-400">
                Enter envía · Shift+Enter no aplica en todos los teclados · corpus demo / no es dictamen
            </p>
        </form>
    </div>
</x-filament-panels::page>
