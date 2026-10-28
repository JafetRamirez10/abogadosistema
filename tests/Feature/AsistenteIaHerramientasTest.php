<?php

declare(strict_types=1);

use App\Actions\ConsultarIaAgente;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Tarea;
use Database\Seeders\LegalCorpusSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('crea una tarea desde el agente de IA sin confirmación', function (): void {
    $this->seed(LegalCorpusSeeder::class);

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    Filament::setTenant($despacho);

    RateLimiter::clear('ia:user:'.$user->getAuthIdentifier());
    RateLimiter::clear('ia:despacho:'.$despacho->getKey());

    $resultado = app(ConsultarIaAgente::class)->handle(
        user: $user,
        pregunta: 'Creá una tarea: Revisar escritura de traspaso',
    );

    expect($resultado['pendientes'])->toBeEmpty()
        ->and($resultado['acciones_ejecutadas'])->not->toBeEmpty()
        ->and(Tarea::query()->where('despacho_id', $despacho->id)->count())->toBeGreaterThan(0)
        ->and(
            Tarea::query()
                ->where('despacho_id', $despacho->id)
                ->where(function ($q): void {
                    $q->where('titulo', 'like', '%Revisar escritura%')
                        ->orWhere('titulo', 'like', '%Tarea creada%');
                })
                ->exists()
        )->toBeTrue();
});

it('pide confirmación antes de eliminar una tarea y solo borra al confirmar', function (): void {
    $this->seed(LegalCorpusSeeder::class);

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    Filament::setTenant($despacho);

    $tarea = Tarea::query()->create([
        'despacho_id' => $despacho->id,
        'asignada_a' => $user->id,
        'creada_por' => $user->id,
        'titulo' => 'Para borrar',
        'prioridad' => PrioridadTarea::Media,
        'estado' => EstadoTarea::Pendiente,
        'orden' => 1,
    ]);

    RateLimiter::clear('ia:user:'.$user->getAuthIdentifier());
    RateLimiter::clear('ia:despacho:'.$despacho->getKey());

    $resultado = app(ConsultarIaAgente::class)->handle(
        user: $user,
        pregunta: "Eliminá la tarea {$tarea->id}",
    );

    expect($resultado['pendientes'])->not->toBeEmpty()
        ->and($resultado['pendientes'][0]['tool'])->toBe('eliminar_tarea')
        ->and($resultado['pendientes'][0]['tipo'])->toBe('eliminar')
        ->and(Tarea::query()->whereKey($tarea->id)->exists())->toBeTrue();

    $confirm = app(ConsultarIaAgente::class)->confirmar($user, $resultado['pendientes'][0]);

    expect($confirm['ok'])->toBeTrue()
        ->and(Tarea::query()->whereKey($tarea->id)->exists())->toBeFalse();
});

it('crea un expediente vía herramienta del toolkit', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    Filament::setTenant($despacho);

    $cliente = Cliente::factory()->create(['despacho_id' => $despacho->id]);

    $toolkit = app(\App\Services\Ai\DespachoAiToolKit::class);
    $result = $toolkit->execute('crear_expediente', [
        'cliente_id' => $cliente->id,
        'titulo' => 'Traspaso finca demo IA',
        'materia' => 'notarial',
    ], $user, (int) $despacho->id);

    expect($result['ok'])->toBeTrue()
        ->and(Expediente::query()->where('despacho_id', $despacho->id)->where('titulo', 'Traspaso finca demo IA')->exists())->toBeTrue();
});
