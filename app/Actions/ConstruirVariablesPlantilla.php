<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\VariablesPlantillaData;
use App\Enums\RolParte;
use App\Models\Expediente;
use App\Models\Parte;
use App\Models\User;
use Carbon\Carbon;

class ConstruirVariablesPlantilla
{
    public function __invoke(Expediente $expediente, ?User $abogado = null): VariablesPlantillaData
    {
        return $this->handle($expediente, $abogado);
    }

    public function handle(Expediente $expediente, ?User $abogado = null): VariablesPlantillaData
    {
        $expediente->loadMissing(['cliente', 'despacho', 'responsable', 'partes']);

        $cliente = $expediente->cliente;
        $abogado ??= $expediente->responsable;

        $contraparte = $expediente->partes->first(
            fn (Parte $parte): bool => $parte->rol === RolParte::Demandado,
        ) ?? $expediente->partes->first();

        return new VariablesPlantillaData([
            'cliente.nombre' => (string) ($cliente->nombre ?? ''),
            'cliente.cedula' => (string) ($cliente->cedula ?? ''),
            'cliente.direccion' => (string) ($cliente->direccion ?? ''),
            'cliente.tipo' => $cliente->tipo->label(),
            'expediente.numero_interno' => (string) $expediente->numero_interno,
            'expediente.numero_judicial' => (string) ($expediente->numero_judicial ?? ''),
            'expediente.titulo' => (string) $expediente->titulo,
            'expediente.materia' => $expediente->materia->label(),
            'expediente.juzgado' => (string) ($expediente->juzgado ?? ''),
            'abogado.nombre' => (string) ($abogado->name ?? ''),
            'despacho.nombre' => (string) ($expediente->despacho->nombre ?? ''),
            'contraparte.nombre' => (string) ($contraparte->nombre ?? ''),
            'fecha_hoy' => Carbon::now('America/Costa_Rica')->format('d/m/Y'),
        ]);
    }
}
