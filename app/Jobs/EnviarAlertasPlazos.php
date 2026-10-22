<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EstadoEvento;
use App\Mail\PlazoProximoMail;
use App\Models\Despacho;
use App\Models\Evento;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class EnviarAlertasPlazos implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $hoy = now('America/Costa_Rica')->startOfDay();

        Despacho::query()->each(function (Despacho $despacho) use ($hoy): void {
            Evento::withoutGlobalScopes()
                ->where('despacho_id', $despacho->getKey())
                ->where('estado', EstadoEvento::Pendiente)
                ->whereNull('alerta_enviada_at')
                ->with(['responsable', 'expediente'])
                ->orderBy('inicio')
                ->each(function (Evento $evento) use ($hoy): void {
                    $inicio = $evento->inicio->timezone('America/Costa_Rica')->startOfDay();
                    $diasRestantes = (int) $hoy->diffInDays($inicio, false);

                    if ($diasRestantes < 0 || $diasRestantes > (int) $evento->dias_aviso) {
                        return;
                    }

                    $responsable = $evento->responsable;

                    if ($responsable === null || blank($responsable->email)) {
                        return;
                    }

                    Mail::to($responsable->email)->send(new PlazoProximoMail($evento));

                    Notification::make()
                        ->title('Plazo próximo')
                        ->body($evento->titulo.' · '.$evento->inicio->timezone('America/Costa_Rica')->format('d/m/Y H:i'))
                        ->warning()
                        ->sendToDatabase($responsable);

                    $evento->forceFill([
                        'alerta_enviada_at' => now('America/Costa_Rica'),
                    ])->save();
                });
        });
    }
}
