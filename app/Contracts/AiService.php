<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\BorradorIaData;
use App\Models\Expediente;

interface AiService
{
    public function resumirExpediente(Expediente $expediente): string;

    public function borradorEscrito(Expediente $expediente, BorradorIaData $data): string;

    public function ultimoTokens(): ?int;
}
