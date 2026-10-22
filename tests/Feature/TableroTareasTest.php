<?php

declare(strict_types=1);

use App\Actions\MoverTareaEnTablero;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Models\Despacho;
use App\Models\Tarea;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mueve una tarea a en progreso y reordena la columna', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $tarea = Tarea::factory()->create([
        'despacho_id' => $despacho->id,
        'asignada_a' => $user->id,
        'creada_por' => $user->id,
        'estado' => EstadoTarea::Pendiente,
        'prioridad' => PrioridadTarea::Alta,
        'orden' => 0,
        'descripcion' => 'Mover al tablero',
    ]);

    $otra = Tarea::factory()->enProgreso()->create([
        'despacho_id' => $despacho->id,
        'asignada_a' => $user->id,
        'creada_por' => $user->id,
        'orden' => 0,
        'descripcion' => 'Ya en progreso',
    ]);

    app(MoverTareaEnTablero::class)->handle(
        $tarea,
        EstadoTarea::EnProgreso,
        $user,
        [$tarea->id, $otra->id],
    );

    expect($tarea->fresh())
        ->estado->toBe(EstadoTarea::EnProgreso)
        ->orden->toBe(0)
        ->completada_at->toBeNull();

    expect($otra->fresh()->orden)->toBe(1);
});

it('al mover a hecho marca completada_at y al devolver limpia la fecha', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $tarea = Tarea::factory()->enProgreso()->create([
        'despacho_id' => $despacho->id,
        'asignada_a' => $user->id,
        'creada_por' => $user->id,
        'descripcion' => 'Cerrar',
    ]);

    app(MoverTareaEnTablero::class)->handle($tarea, EstadoTarea::Completada, $user, [$tarea->id]);

    expect($tarea->fresh())
        ->estado->toBe(EstadoTarea::Completada)
        ->completada_at->not->toBeNull();

    app(MoverTareaEnTablero::class)->handle($tarea->fresh(), EstadoTarea::Pendiente, $user, [$tarea->id]);

    expect($tarea->fresh())
        ->estado->toBe(EstadoTarea::Pendiente)
        ->completada_at->toBeNull();
});
