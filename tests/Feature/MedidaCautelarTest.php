<?php

declare(strict_types=1);

use App\Enums\EstadoMedidaCautelar;
use App\Enums\EtapaPenal;
use App\Enums\Materia;
use App\Enums\TipoMedidaCautelar;
use App\Models\Expediente;
use App\Models\MedidaCautelar;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marca medidas cautelares próximas a revisión como requieren atención', function (): void {
    $despacho = \App\Models\Despacho::factory()->create();
    $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'materia' => Materia::Penal,
        'etapa_penal' => EtapaPenal::Juicio,
        'datos_sensibles' => true,
    ]);

    $medida = MedidaCautelar::query()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $expediente->id,
        'tipo' => TipoMedidaCautelar::PrisionPreventiva,
        'estado' => EstadoMedidaCautelar::Vigente,
        'fecha_inicio' => now('America/Costa_Rica')->subMonths(2)->toDateString(),
        'fecha_revision' => now('America/Costa_Rica')->addDays(2)->toDateString(),
        'fecha_vencimiento' => now('America/Costa_Rica')->addDays(30)->toDateString(),
    ]);

    expect($medida->requiere_atencion)->toBeTrue()
        ->and($expediente->fresh()->esPenal())->toBeTrue();
});
