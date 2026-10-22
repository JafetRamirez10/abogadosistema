<?php

declare(strict_types=1);

use App\Actions\SubirVersionDocumento;
use App\Models\Despacho;
use App\Models\Expediente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('rechaza archivos .php al subir versión de documento', function (): void {
    Storage::fake('local');

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $archivo = UploadedFile::fake()->create('malicioso.php', 100, 'application/x-php');

    expect(fn () => app(SubirVersionDocumento::class)->handle(
        expediente: $expediente,
        archivo: $archivo,
        user: $user,
    ))->toThrow(ValidationException::class);
});

it('rechaza archivos mayores a 10MB', function (): void {
    Storage::fake('local');

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $archivo = UploadedFile::fake()->create('grande.pdf', 11_000, 'application/pdf');

    expect(fn () => app(SubirVersionDocumento::class)->handle(
        expediente: $expediente,
        archivo: $archivo,
        user: $user,
    ))->toThrow(ValidationException::class);
});

it('versiona de v1 a v2 al subir otra versión', function (): void {
    Storage::fake('local');

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $action = app(SubirVersionDocumento::class);

    $documento = $action->handle(
        expediente: $expediente,
        archivo: UploadedFile::fake()->create('escrito.pdf', 200, 'application/pdf'),
        user: $user,
        meta: ['nombre' => 'Escrito inicial'],
    );

    expect($documento->version_actual)->toBe(1)
        ->and($documento->versiones)->toHaveCount(1);

    $documento = $action->handle(
        expediente: $expediente,
        archivo: UploadedFile::fake()->create('escrito-v2.pdf', 250, 'application/pdf'),
        user: $user,
        documento: $documento,
        meta: ['nota' => 'Correcciones'],
    );

    expect($documento->version_actual)->toBe(2)
        ->and($documento->versiones()->count())->toBe(2)
        ->and($documento->ultimaVersion?->version)->toBe(2)
        ->and($documento->ultimaVersion?->nota)->toBe('Correcciones');

    Storage::disk('local')->assertExists($documento->ultimaVersion->ruta);
});
