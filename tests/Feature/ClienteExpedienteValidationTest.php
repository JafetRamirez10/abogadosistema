<?php

declare(strict_types=1);

use App\Actions\CrearCliente;
use App\Actions\CrearExpediente;
use App\DTOs\ClienteData;
use App\DTOs\ExpedienteData;
use App\Enums\Materia;
use App\Enums\TipoCliente;
use App\Enums\TipoHonorario;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('valida cédula física de 9 dígitos y jurídica de 10', function (): void {
    $despacho = Despacho::factory()->create();

    expect(fn () => app(CrearCliente::class)->handle(new ClienteData(
        tipo: TipoCliente::Fisica,
        nombre: 'Juan Pérez',
        cedula: '12345678',
        despachoId: $despacho->id,
    )))->toThrow(ValidationException::class);

    expect(fn () => app(CrearCliente::class)->handle(new ClienteData(
        tipo: TipoCliente::Juridica,
        nombre: 'Sociedad SA',
        cedula: '123456789',
        despachoId: $despacho->id,
    )))->toThrow(ValidationException::class);

    $fisica = app(CrearCliente::class)->handle(new ClienteData(
        tipo: TipoCliente::Fisica,
        nombre: 'Juan Pérez',
        cedula: '1-2345-6789',
        despachoId: $despacho->id,
    ));

    $juridica = app(CrearCliente::class)->handle(new ClienteData(
        tipo: TipoCliente::Juridica,
        nombre: 'Sociedad SA',
        cedula: '3101123456',
        despachoId: $despacho->id,
    ));

    expect($fisica->cedula)->toBe('123456789')
        ->and($juridica->cedula)->toBe('3101123456');
});

it('exige numero_interno único por despacho', function (): void {
    $despacho = Despacho::factory()->create();
    $cliente = Cliente::factory()->create(['despacho_id' => $despacho->id]);
    $responsable = User::factory()->create();
    $responsable->despachos()->attach($despacho);

    $data = new ExpedienteData(
        clienteId: $cliente->id,
        responsableId: $responsable->id,
        numeroInterno: 'EXP-001',
        titulo: 'Caso prueba',
        materia: Materia::Civil,
        fechaInicio: now()->toDateString(),
        tipoHonorario: TipoHonorario::Fijo,
        despachoId: $despacho->id,
    );

    app(CrearExpediente::class)->handle($data);

    expect(fn () => app(CrearExpediente::class)->handle($data))
        ->toThrow(ValidationException::class);
});
