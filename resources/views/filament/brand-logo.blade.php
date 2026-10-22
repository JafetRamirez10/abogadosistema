@php
    $tenant = filament()->getTenant();
    $nombre = $tenant?->nombre ?? 'Gestión Legal';
@endphp
<div class="flex items-center gap-2.5">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5" aria-hidden="true">
            <path d="M12 2a1 1 0 0 1 1 1v1.06l5.2 1.73a1 1 0 0 1 .55 1.48l-2.1 3.36a1 1 0 0 1-1.45.3L12 8.7l-3.2 2.23a1 1 0 0 1-1.45-.3l-2.1-3.36a1 1 0 0 1 .55-1.48L11 4.06V3a1 1 0 0 1 1-1Zm-1 9.4 1-.7 1 .7V20h4a1 1 0 1 1 0 2H7a1 1 0 1 1 0-2h4v-8.6Z"/>
            <path d="M6.5 8.2 5 10.6h3L6.5 8.2Zm11 0L16 10.6h3l-1.5-2.4Z"/>
        </svg>
    </span>
    <div class="min-w-0 leading-tight">
        <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $nombre }}</div>
        <div class="truncate text-xs text-gray-500 dark:text-gray-400">Gestión Legal</div>
    </div>
</div>
