<?php

declare(strict_types=1);

use App\Enums\ConceptoCuotaAlimentaria;
use App\Enums\EstadoCuotaAlimentaria;
use App\Enums\Materia;
use App\Enums\MetodoPago;
use App\Enums\Moneda;
use App\Enums\TipoCasoFamilia;
use App\Models\CuotaAlimentaria;
use App\Models\Expediente;
use App\Models\PagoAlimentario;
use App\Models\PensionAlimentaria;
use App\Services\PensionAlimentariaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('genera cuota mensual y registra pago del obligado actualizando mora', function (): void {
    $despacho = \App\Models\Despacho::factory()->create();
    $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'materia' => Materia::Familia,
        'tipo_caso_familia' => TipoCasoFamilia::PensionAlimentaria,
    ]);

    $pension = PensionAlimentaria::query()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $expediente->id,
        'cuota_mensual' => '100000.00',
        'moneda' => Moneda::CRC,
        'dia_vencimiento' => 5,
        'incluye_aguinaldo' => true,
        'incluye_salario_escolar' => true,
        'fecha_inicio' => now('America/Costa_Rica')->startOfMonth()->toDateString(),
        'activa' => true,
    ]);

    $service = app(PensionAlimentariaService::class);
    $cuota = $service->generarCuotaMensual($pension, now('America/Costa_Rica'));

    expect($cuota)
        ->concepto->toBe(ConceptoCuotaAlimentaria::Mensual)
        ->estado->toBe(EstadoCuotaAlimentaria::Pendiente)
        ->monto->toBe('100000.00');

    $segunda = $service->generarCuotaMensual($pension, now('America/Costa_Rica'));
    expect($segunda->is($cuota))->toBeTrue();

    $pago = PagoAlimentario::query()->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $expediente->id,
        'pension_alimentaria_id' => $pension->id,
        'cuota_alimentaria_id' => $cuota->id,
        'monto' => '40000.00',
        'moneda' => Moneda::CRC,
        'fecha' => now('America/Costa_Rica')->toDateString(),
        'metodo' => MetodoPago::Sinpe,
    ]);

    $service->registrarPago($pago);

    $cuota->refresh();
    expect($cuota)
        ->estado->toBe(EstadoCuotaAlimentaria::Parcial)
        ->monto_pagado->toBe('40000.00')
        ->saldo->toBe('60000.00');

    CuotaAlimentaria::query()->whereKey($cuota->id)->update([
        'vence_el' => now('America/Costa_Rica')->subDays(3)->toDateString(),
    ]);
    $cuota->refresh();

    expect($cuota->en_mora)->toBeTrue()
        ->and($service->totalEnMora($pension))->toBe('60000.00');
});
