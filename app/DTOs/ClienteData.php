<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TipoCliente;

readonly class ClienteData
{
    public function __construct(
        public TipoCliente $tipo,
        public string $nombre,
        public string $cedula,
        public ?string $telefono = null,
        public ?string $email = null,
        public ?string $direccion = null,
        public ?string $notas = null,
        public ?int $despachoId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $tipo = $data['tipo'] instanceof TipoCliente
            ? $data['tipo']
            : TipoCliente::from((string) $data['tipo']);

        return new self(
            tipo: $tipo,
            nombre: (string) $data['nombre'],
            cedula: (string) $data['cedula'],
            telefono: isset($data['telefono']) ? (string) $data['telefono'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            direccion: isset($data['direccion']) ? (string) $data['direccion'] : null,
            notas: isset($data['notas']) ? (string) $data['notas'] : null,
            despachoId: isset($data['despacho_id']) ? (int) $data['despacho_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'despacho_id' => $this->despachoId,
            'tipo' => $this->tipo,
            'nombre' => $this->nombre,
            'cedula' => $this->cedula,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'notas' => $this->notas,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
