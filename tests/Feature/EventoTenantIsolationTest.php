<?php

declare(strict_types=1);

use App\Models\Despacho;
use App\Models\Evento;
use App\Policies\EventoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('eventos de otro despacho no son visibles por tenant', function (): void {
    $despachoA = Despacho::factory()->create();
    $despachoB = Despacho::factory()->create();

    $userA = $this->actingAsTenantUser('abogado', $despachoA);

    $eventoB = Evento::factory()->create([
        'despacho_id' => $despachoB->id,
        'titulo' => 'Evento Tenant B',
    ]);

    $policy = new EventoPolicy;

    expect($policy->view($userA, $eventoB))->toBeFalse();

    expect(Evento::query()->whereKey($eventoB->id)->exists())->toBeFalse();

    expect(Evento::withoutGlobalScopes()->whereKey($eventoB->id)->exists())->toBeTrue();
});
