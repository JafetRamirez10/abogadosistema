<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConceptoCuotaAlimentaria;
use App\Enums\EstadoCuotaAlimentaria;
use App\Models\CuotaAlimentaria;
use App\Models\PagoAlimentario;
use App\Models\PensionAlimentaria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PensionAlimentariaService
{
    public function generarCuotaMensual(PensionAlimentaria $pension, ?Carbon $referencia = null): CuotaAlimentaria
    {
        $ref = ($referencia ?? now('America/Costa_Rica'))->copy()->timezone('America/Costa_Rica');
        $periodo = $ref->format('Y-m');
        $dia = max(1, min(28, (int) $pension->dia_vencimiento));
        $vence = Carbon::create($ref->year, $ref->month, $dia, 0, 0, 0, 'America/Costa_Rica');

        $existente = CuotaAlimentaria::query()
            ->where('pension_alimentaria_id', $pension->getKey())
            ->where('concepto', ConceptoCuotaAlimentaria::Mensual)
            ->where('periodo', $periodo)
            ->first();

        if ($existente !== null) {
            return $existente;
        }

        return CuotaAlimentaria::query()->create([
            'despacho_id' => $pension->despacho_id,
            'expediente_id' => $pension->expediente_id,
            'pension_alimentaria_id' => $pension->getKey(),
            'concepto' => ConceptoCuotaAlimentaria::Mensual,
            'periodo' => $periodo,
            'monto' => $pension->cuota_mensual,
            'monto_pagado' => 0,
            'moneda' => $pension->moneda,
            'vence_el' => $vence->toDateString(),
            'estado' => EstadoCuotaAlimentaria::Pendiente,
        ]);
    }

    public function registrarPago(PagoAlimentario $pago): void
    {
        DB::transaction(function () use ($pago): void {
            if ($pago->cuota_alimentaria_id === null) {
                return;
            }

            $cuota = CuotaAlimentaria::query()->lockForUpdate()->find($pago->cuota_alimentaria_id);
            if ($cuota === null) {
                throw new InvalidArgumentException('La cuota alimentaria no existe.');
            }

            if ((int) $cuota->pension_alimentaria_id !== (int) $pago->pension_alimentaria_id) {
                throw new InvalidArgumentException('La cuota no pertenece a esta pensión.');
            }

            $cuota->monto_pagado = number_format(
                (float) $cuota->monto_pagado + (float) $pago->monto,
                2,
                '.',
                '',
            );
            $cuota->recalcularEstado();
        });
    }

    public function totalEnMora(PensionAlimentaria $pension): string
    {
        $hoy = now('America/Costa_Rica')->toDateString();

        $total = CuotaAlimentaria::query()
            ->where('pension_alimentaria_id', $pension->getKey())
            ->whereIn('estado', [
                EstadoCuotaAlimentaria::Pendiente->value,
                EstadoCuotaAlimentaria::Parcial->value,
            ])
            ->whereDate('vence_el', '<', $hoy)
            ->get()
            ->sum(fn (CuotaAlimentaria $c): float => (float) $c->saldo);

        return number_format($total, 2, '.', '');
    }
}
