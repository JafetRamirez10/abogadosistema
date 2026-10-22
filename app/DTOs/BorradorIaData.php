<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TipoEscritoIa;
use InvalidArgumentException;

readonly class BorradorIaData
{
    public string $instrucciones;

    public function __construct(
        string $instrucciones,
        public TipoEscritoIa $tipoEscrito,
        public ?string $tono = null,
    ) {
        $instrucciones = trim($instrucciones);

        if ($instrucciones === '') {
            throw new InvalidArgumentException('Las instrucciones del borrador no pueden estar vacías.');
        }

        if (mb_strlen($instrucciones) > 2000) {
            throw new InvalidArgumentException('Las instrucciones no pueden superar 2000 caracteres.');
        }

        $this->instrucciones = $instrucciones;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $tipo = $data['tipo_escrito'] ?? $data['tipoEscrito'] ?? TipoEscritoIa::EscritoVarios;

        return new self(
            instrucciones: (string) ($data['instrucciones'] ?? ''),
            tipoEscrito: $tipo instanceof TipoEscritoIa
                ? $tipo
                : TipoEscritoIa::from((string) $tipo),
            tono: isset($data['tono']) ? (string) $data['tono'] : null,
        );
    }
}
