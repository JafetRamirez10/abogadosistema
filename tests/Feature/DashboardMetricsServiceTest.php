<?php

declare(strict_types=1);

use App\Enums\EstadoDocumento;
use App\Enums\EstadoEvento;
use App\Enums\EstadoExpediente;
use App\Enums\TipoEvento;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Expediente;
use App\Services\DashboardMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('cuenta métricas del dashboard desde la BD', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $fecha = CarbonImmutable::parse('2026-04-15', 'America/Costa_Rica');

    $clientes = Cliente::factory()->count(3)->create([
        'despacho_id' => $despacho->id,
        'created_at' => $fecha->subDays(2),
        'updated_at' => $fecha->subDays(2),
    ]);

    $activo = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'cliente_id' => $clientes[0]->id,
        'responsable_id' => $user->id,
        'estado' => EstadoExpediente::EnProgreso,
        'created_at' => $fecha->subDays(1),
        'updated_at' => $fecha->subDays(1),
    ]);

    Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'cliente_id' => $clientes[1]->id,
        'responsable_id' => $user->id,
        'estado' => EstadoExpediente::Cerrado,
        'created_at' => $fecha->subDays(1),
        'updated_at' => $fecha->subDays(1),
    ]);

    Evento::factory()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $activo->id,
        'responsable_id' => $user->id,
        'tipo' => TipoEvento::Audiencia,
        'estado' => EstadoEvento::Pendiente,
        'inicio' => $fecha->setTime(10, 0),
    ]);
    Evento::factory()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $activo->id,
        'responsable_id' => $user->id,
        'tipo' => TipoEvento::Audiencia,
        'estado' => EstadoEvento::Cancelada,
        'inicio' => $fecha->setTime(11, 0),
    ]);
    Evento::factory()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $activo->id,
        'responsable_id' => $user->id,
        'tipo' => TipoEvento::Cita,
        'estado' => EstadoEvento::Pendiente,
        'inicio' => $fecha->setTime(12, 0),
    ]);

    Documento::factory()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $activo->id,
        'estado' => EstadoDocumento::PendienteFirma,
        'created_at' => $fecha->subDay(),
        'updated_at' => $fecha->subDay(),
    ]);
    Documento::factory()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $activo->id,
        'estado' => EstadoDocumento::Aprobado,
        'created_at' => $fecha->subDay(),
        'updated_at' => $fecha->subDay(),
    ]);

    $resumen = app(DashboardMetricsService::class)->resumen($fecha);

    expect($resumen['casos_activos']['valor'])->toBe(1)
        ->and($resumen['audiencias_hoy']['valor'])->toBe(1)
        ->and($resumen['clientes_totales']['valor'])->toBe(3)
        ->and($resumen['documentos_pendientes']['valor'])->toBe(1);
});

it('no inventa variación cuando el período anterior es cero', function (): void {
    $service = app(DashboardMetricsService::class);

    expect($service->variacionPorcentual(5, 0))->toBeNull()
        ->and($service->variacionPorcentual(10, 5))->toBe(100.0)
        ->and($service->variacionPorcentual(4, 8))->toBe(-50.0);
});
