<?php

declare(strict_types=1);

use App\Filament\Resources\ExpedienteResource\Pages\ViewExpediente;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('asistente no tiene permiso ver_montos', function (): void {
    $despacho = Despacho::factory()->create();
    $asistente = $this->actingAsTenantUser('asistente', $despacho);

    expect($asistente->can('ver_montos'))->toBeFalse();
});

it('asistente no ve montos en la vista del expediente', function (): void {
    $despacho = Despacho::factory()->create();
    $asistente = $this->actingAsTenantUser('asistente', $despacho);
    $responsable = User::factory()->create();
    $responsable->despachos()->attach($despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'responsable_id' => $responsable->id,
        'cuantia' => 1_500_000.00,
        'honorario_pactado' => 250_000.00,
    ]);

    Livewire::actingAs($asistente)
        ->test(ViewExpediente::class, ['record' => $expediente->getRouteKey()])
        ->assertSuccessful()
        ->assertDontSee('Honorario pactado')
        ->assertDontSee('1,500,000')
        ->assertDontSee('250,000');
});

it('admin sí tiene ver_montos', function (): void {
    $despacho = Despacho::factory()->create();
    $admin = $this->actingAsTenantUser('admin', $despacho);

    expect($admin->can('ver_montos'))->toBeTrue();
});
