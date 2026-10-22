<?php

declare(strict_types=1);

use App\Actions\GenerarDocumentoDesdePlantilla;
use App\Enums\FormatoSalidaPlantilla;
use App\Enums\TipoActuacion;
use App\Models\Actuacion;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Plantilla;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('genera DOCX y PDF desde plantilla con Storage fake', function (): void {
    Storage::fake('local');

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $plantilla = Plantilla::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'nombre' => 'Escrito de apersonamiento',
        'formato_salida' => FormatoSalidaPlantilla::Ambos,
        'contenido' => 'Yo {{ cliente.nombre }} en el expediente {{ expediente.numero_interno }}.',
    ]);

    $documentos = app(GenerarDocumentoDesdePlantilla::class)->handle(
        expediente: $expediente,
        plantilla: $plantilla,
        user: $user,
    );

    expect($documentos)->toHaveCount(2);

    foreach ($documentos as $documento) {
        expect($documento->plantilla_id)->toBe($plantilla->getKey())
            ->and($documento->version_actual)->toBe(1)
            ->and($documento->ultimaVersion)->not->toBeNull();

        Storage::disk('local')->assertExists($documento->ultimaVersion->ruta);
    }

    expect(Actuacion::query()->where('tipo', TipoActuacion::Escrito)->count())->toBe(2);
});

it('genera solo PDF cuando el formato de plantilla es pdf', function (): void {
    Storage::fake('local');

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $plantilla = Plantilla::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'formato_salida' => FormatoSalidaPlantilla::Pdf,
        'contenido' => 'Fecha {{ fecha_hoy }}',
    ]);

    $documentos = app(GenerarDocumentoDesdePlantilla::class)->handle(
        expediente: $expediente,
        plantilla: $plantilla,
        user: $user,
    );

    expect($documentos)->toHaveCount(1)
        ->and($documentos[0]->ultimaVersion?->mime)->toBe('application/pdf');
});
