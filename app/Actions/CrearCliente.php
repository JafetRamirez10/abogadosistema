<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ClienteData;
use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Models\Despacho;
use App\Support\Cedula;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CrearCliente
{
    public function __invoke(ClienteData $data): Cliente
    {
        return $this->handle($data);
    }

    public function handle(ClienteData $data): Cliente
    {
        $despachoId = $data->despachoId ?? $this->currentDespachoId();
        $cedula = Cedula::normalizar($data->cedula);
        $digitosEsperados = $data->tipo === TipoCliente::Fisica ? 9 : 10;

        $payload = [
            ...$data->toArray(),
            'despacho_id' => $despachoId,
            'cedula' => $cedula,
            'cedula_hash' => Cedula::hash($cedula),
        ];

        $validator = Validator::make($payload, [
            'despacho_id' => ['required', 'integer', 'exists:despachos,id'],
            'tipo' => ['required', Rule::enum(TipoCliente::class)],
            'nombre' => ['required', 'string', 'max:255'],
            'cedula' => [
                'required',
                'string',
                "digits:{$digitosEsperados}",
            ],
            'cedula_hash' => [
                'required',
                'string',
                'size:64',
                Rule::unique('clientes', 'cedula_hash')->where(
                    fn ($query) => $query->where('despacho_id', $despachoId),
                ),
            ],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ], [
            'cedula.digits' => "La cédula debe tener {$digitosEsperados} dígitos (sin guiones).",
            'cedula_hash.unique' => 'Ya existe un cliente con esta cédula en el despacho.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Cliente::query()->create($validator->validated());
    }

    private function currentDespachoId(): ?int
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Despacho ? (int) $tenant->getKey() : null;
    }
}
