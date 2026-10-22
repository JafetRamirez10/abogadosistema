<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\EstadoExpediente;
use App\Enums\Materia;
use App\Enums\Moneda;
use App\Enums\TipoHonorario;
use Carbon\CarbonInterface;

readonly class ExpedienteData
{
    public function __construct(
        public int $clienteId,
        public int $responsableId,
        public string $numeroInterno,
        public string $titulo,
        public Materia $materia,
        public CarbonInterface|string $fechaInicio,
        public TipoHonorario $tipoHonorario,
        public ?string $numeroJudicial = null,
        public EstadoExpediente $estado = EstadoExpediente::Pendiente,
        public ?string $juzgado = null,
        public ?string $cuantia = null,
        public Moneda $moneda = Moneda::CRC,
        public ?string $honorarioPactado = null,
        public ?string $porcentajeHonorario = null,
        public ?string $descripcion = null,
        public ?int $despachoId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clienteId: (int) $data['cliente_id'],
            responsableId: (int) $data['responsable_id'],
            numeroInterno: (string) $data['numero_interno'],
            titulo: (string) $data['titulo'],
            materia: $data['materia'] instanceof Materia
                ? $data['materia']
                : Materia::from((string) $data['materia']),
            fechaInicio: $data['fecha_inicio'],
            tipoHonorario: $data['tipo_honorario'] instanceof TipoHonorario
                ? $data['tipo_honorario']
                : TipoHonorario::from((string) $data['tipo_honorario']),
            numeroJudicial: isset($data['numero_judicial']) ? (string) $data['numero_judicial'] : null,
            estado: isset($data['estado'])
                ? ($data['estado'] instanceof EstadoExpediente
                    ? $data['estado']
                    : EstadoExpediente::from((string) $data['estado']))
                : EstadoExpediente::Pendiente,
            juzgado: isset($data['juzgado']) ? (string) $data['juzgado'] : null,
            cuantia: isset($data['cuantia']) ? (string) $data['cuantia'] : null,
            moneda: isset($data['moneda'])
                ? ($data['moneda'] instanceof Moneda
                    ? $data['moneda']
                    : Moneda::from((string) $data['moneda']))
                : Moneda::CRC,
            honorarioPactado: isset($data['honorario_pactado']) ? (string) $data['honorario_pactado'] : null,
            porcentajeHonorario: isset($data['porcentaje_honorario']) ? (string) $data['porcentaje_honorario'] : null,
            descripcion: isset($data['descripcion']) ? (string) $data['descripcion'] : null,
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
            'cliente_id' => $this->clienteId,
            'responsable_id' => $this->responsableId,
            'numero_interno' => $this->numeroInterno,
            'numero_judicial' => $this->numeroJudicial,
            'titulo' => $this->titulo,
            'materia' => $this->materia,
            'estado' => $this->estado,
            'juzgado' => $this->juzgado,
            'fecha_inicio' => $this->fechaInicio,
            'cuantia' => $this->cuantia,
            'moneda' => $this->moneda,
            'tipo_honorario' => $this->tipoHonorario,
            'honorario_pactado' => $this->honorarioPactado,
            'porcentaje_honorario' => $this->porcentajeHonorario,
            'descripcion' => $this->descripcion,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
