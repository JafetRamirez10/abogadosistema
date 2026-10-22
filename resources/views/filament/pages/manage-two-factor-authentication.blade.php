<x-filament-panels::page>
    <div class="space-y-6">
        @if (auth()->user()->hasTwoFactorEnabled())
            <p class="text-sm text-gray-600 dark:text-gray-300">
                La autenticación en dos pasos está <strong>activa</strong> en tu cuenta.
            </p>

            <x-filament::button color="danger" wire:click="disable" wire:confirm="¿Desactivar 2FA?">
                Desactivar 2FA
            </x-filament::button>
        @else
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Protegé tu cuenta con un código temporal (TOTP) compatible con Google Authenticator u otras apps.
            </p>

            @if ($pendingSecret)
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="mb-2 text-sm font-medium">Secreto (ingresalo manualmente en tu app):</p>
                    <code class="text-sm break-all">{{ $pendingSecret }}</code>
                </div>

                {{ $this->form }}

                <x-filament::button wire:click="confirm">
                    Confirmar y activar
                </x-filament::button>
            @else
                <x-filament::button wire:click="startSetup">
                    Generar secreto 2FA
                </x-filament::button>
            @endif
        @endif
    </div>
</x-filament-panels::page>
