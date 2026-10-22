<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MetodoPago;
use App\Enums\Moneda;
use App\Models\Despacho;
use App\Models\Pago;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegistrarPago
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Pago
    {
        return $this->handle($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Pago
    {
        $despachoId = $data['despacho_id'] ?? $this->currentDespachoId();

        $payload = [
            ...$data,
            'despacho_id' => $despachoId,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'moneda' => $data['moneda'] ?? Moneda::CRC->value,
        ];

        $validator = Validator::make($payload, [
            'despacho_id' => ['required', 'integer', 'exists:despachos,id'],
            'expediente_id' => ['required', 'integer', 'exists:expedientes,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'moneda' => ['required', Rule::enum(Moneda::class)],
            'fecha' => ['required', 'date'],
            'metodo' => ['required', Rule::enum(MetodoPago::class)],
            'referencia' => ['nullable', 'string', 'max:255'],
            'nota' => ['nullable', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Pago::query()->create($validator->validated());
    }

    private function currentDespachoId(): ?int
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Despacho ? (int) $tenant->getKey() : null;
    }
}
