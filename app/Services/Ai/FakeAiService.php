<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\BorradorIaData;
use App\Models\Expediente;

final class FakeAiService extends AbstractAiService
{
    public function resumirExpediente(Expediente $expediente): string
    {
        $this->ultimoTokens = null;
        $contexto = $this->contextBuilder->build($expediente);

        return "Resumen simulado (FakeAiService) del expediente {$expediente->numero_interno}.\n\n"
            ."Este es un borrador para revisión del abogado.\n\n"
            ."Contexto utilizado:\n{$contexto}";
    }

    public function borradorEscrito(Expediente $expediente, BorradorIaData $data): string
    {
        $this->ultimoTokens = null;
        $contexto = $this->contextBuilder->build($expediente);

        return "Borrador simulado (FakeAiService) — {$data->tipoEscrito->label()}.\n\n"
            ."Expediente: {$expediente->numero_interno}\n"
            ."Instrucciones: {$data->instrucciones}\n\n"
            ."Este es un borrador para revisión del abogado. No cite leyes inventadas.\n\n"
            ."Contexto:\n{$contexto}";
    }
}
