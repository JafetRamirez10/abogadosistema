<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Moneda;
use App\Models\Despacho;
use App\Models\Hora;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegistrarHora
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Hora
    {
        return $this->handle($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Hora
    {
        $despachoId = $data['despacho_id'] ?? $this->currentDespachoId();

        $payload = [
            ...$data,
            'despacho_id' => $despachoId,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'moneda' => $data['moneda'] ?? Moneda::CRC->value,
            'facturable' => $data['facturable'] ?? true,
        ];

        $validator = Validator::make($payload, [
            'despacho_id' => ['required', 'integer', 'exists:despachos,id'],
            'expediente_id' => ['required', 'integer', 'exists:expedientes,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'fecha' => ['required', 'date'],
            'minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            'descripcion' => ['required', 'string', 'max:255'],
            'tarifa' => ['nullable', 'numeric', 'min:0'],
            'moneda' => ['required', Rule::enum(Moneda::class)],
            'facturable' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Hora::query()->create($validator->validated());
    }

    private function currentDespachoId(): ?int
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Despacho ? (int) $tenant->getKey() : null;
    }
}
