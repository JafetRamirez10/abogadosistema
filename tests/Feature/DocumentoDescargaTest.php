<?php

declare(strict_types=1);

use App\Models\Despacho;
use App\Models\Documento;
use App\Models\DocumentoVersion;
use App\Models\Expediente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('devuelve 403 al descargar sin permiso de despacho', function (): void {
    Storage::fake('local');

    $despachoA = Despacho::factory()->create();
    $despachoB = Despacho::factory()->create();
    RolesAndPermissionsSeeder::seedRolesForDespacho($despachoA);
    RolesAndPermissionsSeeder::seedRolesForDespacho($despachoB);

    $owner = User::factory()->create();
    $owner->despachos()->attach($despachoA);
    setPermissionsTeamId($despachoA->getKey());
    $owner->assignRole('abogado');

    $intruso = User::factory()->create();
    $intruso->despachos()->attach($despachoB);
    setPermissionsTeamId($despachoB->getKey());
    $intruso->assignRole('abogado');

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despachoA->getKey(),
        'responsable_id' => $owner->getKey(),
    ]);

    $documento = Documento::factory()->create([
        'despacho_id' => $despachoA->getKey(),
        'expediente_id' => $expediente->getKey(),
    ]);

    $ruta = 'despachos/'.$despachoA->getKey().'/expedientes/'.$expediente->getKey().'/test.pdf';
    Storage::disk('local')->put($ruta, '%PDF-1.4 fake');

    $version = DocumentoVersion::factory()->create([
        'despacho_id' => $despachoA->getKey(),
        'documento_id' => $documento->getKey(),
        'user_id' => $owner->getKey(),
        'ruta' => $ruta,
        'version' => 1,
    ]);

    $url = URL::temporarySignedRoute(
        'documentos.descargar',
        now()->addMinutes(5),
        ['documento' => $documento->getKey(), 'version' => $version->getKey()],
    );

    $this->actingAs($intruso)
        ->get($url)
        ->assertForbidden();
});

it('permite descargar con firma válida y permiso', function (): void {
    Storage::fake('local');

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $documento = Documento::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'expediente_id' => $expediente->getKey(),
        'nombre' => 'Demanda',
    ]);

    $ruta = 'despachos/'.$despacho->getKey().'/expedientes/'.$expediente->getKey().'/ok.pdf';
    Storage::disk('local')->put($ruta, '%PDF-1.4 contenido');

    $version = DocumentoVersion::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'documento_id' => $documento->getKey(),
        'user_id' => $user->getKey(),
        'ruta' => $ruta,
        'version' => 1,
        'mime' => 'application/pdf',
    ]);

    $url = URL::temporarySignedRoute(
        'documentos.descargar',
        now()->addMinutes(5),
        ['documento' => $documento->getKey(), 'version' => $version->getKey()],
    );

    $this->get($url)
        ->assertOk()
        ->assertHeader('content-disposition');
});
