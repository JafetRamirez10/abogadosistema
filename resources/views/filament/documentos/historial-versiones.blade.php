{{-- Historial de versiones: solo escape estandar Blade. --}}
<div class="space-y-3 text-sm">
    @forelse ($versiones as $version)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <div class="font-medium">Versión {{ $version->version }}</div>
            <div class="text-gray-500 dark:text-gray-400">
                {{ $version->created_at?->format('d/m/Y H:i') }}
                · {{ $version->user?->name ?? '—' }}
                · {{ number_format($version->tamano / 1024, 1) }} KB
            </div>
            @if (filled($version->nota))
                <div class="mt-1">{{ $version->nota }}</div>
            @endif
            <div class="mt-1 font-mono text-xs text-gray-400 truncate" title="{{ $version->sha256 }}">
                SHA-256: {{ $version->sha256 }}
            </div>
        </div>
    @empty
        <p>Sin versiones.</p>
    @endforelse
</div>
