<?php

declare(strict_types=1);

use App\Actions\RegistrarPago;
use App\Enums\MetodoPago;
use App\Enums\Moneda;
use App\Models\Activity;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Hora;
use App\Models\Pago;
use App\Models\User;
use App\Support\Dinero;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('calcula el saldo pendiente de María (honorario − pagos)', function (): void {
    $despacho = Despacho::factory()->create();
    $responsable = User::factory()->create();
    $responsable->despachos()->attach($despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'responsable_id' => $responsable->id,
        'honorario_pactado' => 600_000,
        'moneda' => Moneda::CRC,
        'titulo' => 'Caso María',
    ]);

    Pago::factory()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $expediente->id,
        'user_id' => $responsable->id,
        'monto' => 100_000,
        'moneda' => Moneda::CRC,
        'metodo' => MetodoPago::Transferencia,
    ]);

    $expediente->refresh();

    expect($expediente->total_pagado)->toBe('100000.00')
        ->and($expediente->saldo_pendiente)->toBe('500000.00')
        ->and(formatear_monto($expediente->saldo_pendiente, Moneda::CRC))->toBe('₡500.000');
});

it('asistente recibe 403 en horas/pagos', function (): void {
    $despacho = Despacho::factory()->create();
    $asistente = $this->actingAsTenantUser('asistente', $despacho);

    expect($asistente->can('viewAny', Hora::class))->toBeFalse()
        ->and($asistente->can('create', Hora::class))->toBeFalse()
        ->and($asistente->can('viewAny', Pago::class))->toBeFalse()
        ->and($asistente->can('create', Pago::class))->toBeFalse();

    expect(fn () => Gate::authorize('viewAny', Hora::class))
        ->toThrow(AuthorizationException::class);
    expect(fn () => Gate::authorize('create', Pago::class))
        ->toThrow(AuthorizationException::class);
});

it('registra activity log al crear un pago', function (): void {
    $despacho = Despacho::factory()->create();
    $admin = $this->actingAsTenantUser('admin', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'responsable_id' => $admin->id,
        'honorario_pactado' => 600_000,
        'moneda' => Moneda::CRC,
    ]);

    $pago = app(RegistrarPago::class)->handle([
        'despacho_id' => $despacho->id,
        'expediente_id' => $expediente->id,
        'monto' => 100_000,
        'moneda' => Moneda::CRC,
        'fecha' => now('America/Costa_Rica')->toDateString(),
        'metodo' => MetodoPago::Sinpe,
        'referencia' => 'SINPE-001',
        'user_id' => $admin->id,
    ]);

    expect(
        Activity::query()
            ->where('subject_type', Pago::class)
            ->where('subject_id', $pago->id)
            ->exists()
    )->toBeTrue();
});

it('formatea montos CRC y USD', function (): void {
    expect(Dinero::formatear(100_000, Moneda::CRC))->toBe('₡100.000')
        ->and(Dinero::formatear(500, Moneda::USD))->toBe('$500.00')
        ->and(formatear_monto(1_500_000, Moneda::CRC))->toBe('₡1.500.000');
});
