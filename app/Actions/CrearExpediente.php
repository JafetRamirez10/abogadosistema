<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ExpedienteData;
use App\Enums\EstadoExpediente;
use App\Enums\Materia;
use App\Enums\Moneda;
use App\Enums\TipoHonorario;
use App\Models\Despacho;
use App\Models\Expediente;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CrearExpediente
{
    public function __invoke(ExpedienteData $data): Expediente
    {
        return $this->handle($data);
    }

    public function handle(ExpedienteData $data): Expediente
    {
        $despachoId = $data->despachoId ?? $this->currentDespachoId();

        $payload = [
            ...$data->toArray(),
            'despacho_id' => $despachoId,
        ];

        $validator = Validator::make($payload, [
            'despacho_id' => ['required', 'integer', 'exists:despachos,id'],
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'numero_interno' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expedientes', 'numero_interno')->where(
                    fn ($query) => $query->where('despacho_id', $despachoId),
                ),
            ],
            'numero_judicial' => ['nullable', 'string', 'max:100'],
            'titulo' => ['required', 'string', 'max:255'],
            'materia' => ['required', Rule::enum(Materia::class)],
            'estado' => ['required', Rule::enum(EstadoExpediente::class)],
            'juzgado' => ['nullable', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'cuantia' => ['nullable', 'numeric'],
            'moneda' => ['required', Rule::enum(Moneda::class)],
            'tipo_honorario' => ['required', Rule::enum(TipoHonorario::class)],
            'honorario_pactado' => ['nullable', 'numeric'],
            'porcentaje_honorario' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'descripcion' => ['nullable', 'string'],
        ], [
            'numero_interno.unique' => 'El número interno ya está en uso en este despacho.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return Expediente::query()->create($validator->validated());
    }

    private function currentDespachoId(): ?int
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Despacho ? (int) $tenant->getKey() : null;
    }
}
