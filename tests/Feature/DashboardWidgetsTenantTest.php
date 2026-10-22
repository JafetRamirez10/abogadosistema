<?php

declare(strict_types=1);

use App\Enums\EstadoExpediente;
use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Filament\Widgets\CasosRecientesWidget;
use App\Filament\Widgets\DistribucionCasosWidget;
use App\Filament\Widgets\MisTareasWidget;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Tarea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('los widgets del dashboard respetan el tenant activo', function (): void {
    $despachoA = Despacho::factory()->create();
    $despachoB = Despacho::factory()->create();

    $userA = $this->actingAsTenantUser('abogado', $despachoA);

    $clienteA = Cliente::factory()->create(['despacho_id' => $despachoA->id]);
    $clienteB = Cliente::factory()->create(['despacho_id' => $despachoB->id]);

    $casoA = Expediente::factory()->create([
        'despacho_id' => $despachoA->id,
        'cliente_id' => $clienteA->id,
        'responsable_id' => $userA->id,
        'estado' => EstadoExpediente::EnProgreso,
        'titulo' => 'Caso Tenant A',
    ]);

    Expediente::factory()->create([
        'despacho_id' => $despachoB->id,
        'cliente_id' => $clienteB->id,
        'estado' => EstadoExpediente::EnProgreso,
        'titulo' => 'Caso Tenant B',
    ]);

    Tarea::factory()->create([
        'despacho_id' => $despachoA->id,
        'expediente_id' => $casoA->id,
        'asignada_a' => $userA->id,
        'creada_por' => $userA->id,
        'estado' => EstadoTarea::Pendiente,
        'prioridad' => PrioridadTarea::Alta,
        'descripcion' => 'Tarea A',
    ]);

    Tarea::factory()->create([
        'despacho_id' => $despachoB->id,
        'asignada_a' => $userA->id,
        'creada_por' => $userA->id,
        'estado' => EstadoTarea::Pendiente,
        'descripcion' => 'Tarea B',
    ]);

    expect(Expediente::query()->count())->toBe(1)
        ->and(Tarea::query()->pendientesDe($userA)->count())->toBe(1);

    Livewire::test(CasosRecientesWidget::class)
        ->assertCanSeeTableRecords(Expediente::query()->get())
        ->assertCanNotSeeTableRecords(
            Expediente::withoutGlobalScopes()->where('despacho_id', $despachoB->id)->get()
        );

    $chart = Livewire::test(DistribucionCasosWidget::class);
    $data = invade($chart->instance())->getData();
    expect(array_sum($data['datasets'][0]['data']))->toBe(1);

    $misTareas = Livewire::test(MisTareasWidget::class);
    expect($misTareas->instance()->getTareas()->pluck('descripcion')->all())
        ->toBe(['Tarea A']);
});
