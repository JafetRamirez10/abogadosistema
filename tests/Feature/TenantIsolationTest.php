<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Despacho;
use App\Policies\ClientePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aísla registros por tenant despacho', function (): void {
    $despachoA = Despacho::factory()->create();
    $despachoB = Despacho::factory()->create();

    $userA = $this->actingAsTenantUser('abogado', $despachoA);

    $clienteB = Cliente::factory()->create([
        'despacho_id' => $despachoB->id,
        'nombre' => 'Cliente Tenant B',
    ]);

    $policy = new ClientePolicy;

    expect($policy->view($userA, $clienteB))->toBeFalse();

    // Con tenant A activo, el global scope no debe devolver clientes de B.
    expect(Cliente::query()->whereKey($clienteB->id)->exists())->toBeFalse();

    // Sin scope de tenant, el registro existe (confirma que no es 404 por borrado).
    expect(Cliente::withoutGlobalScopes()->whereKey($clienteB->id)->exists())->toBeTrue();
});
