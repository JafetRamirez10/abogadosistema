<?php

declare(strict_types=1);

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Filament\Widgets\MisTareasWidget;
use App\Models\Despacho;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('completa una tarea propia desde el widget Livewire', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $tarea = Tarea::factory()->create([
        'despacho_id' => $despacho->id,
        'asignada_a' => $user->id,
        'creada_por' => $user->id,
        'estado' => EstadoTarea::Pendiente,
        'prioridad' => PrioridadTarea::Media,
        'descripcion' => 'Preparar memorial',
    ]);

    Livewire::test(MisTareasWidget::class)
        ->call('completar', $tarea->id)
        ->assertNotified();

    expect($tarea->fresh())
        ->estado->toBe(EstadoTarea::Completada)
        ->completada_at->not->toBeNull();
});

it('no permite completar una tarea asignada a otro usuario', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    $otro = User::factory()->create();
    $otro->despachos()->attach($despacho);

    $tarea = Tarea::factory()->create([
        'despacho_id' => $despacho->id,
        'asignada_a' => $otro->id,
        'creada_por' => $user->id,
        'estado' => EstadoTarea::Pendiente,
        'descripcion' => 'Ajena',
    ]);

    Livewire::test(MisTareasWidget::class)
        ->call('completar', $tarea->id);

    expect($tarea->fresh()->estado)->toBe(EstadoTarea::Pendiente);
});
