<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PeriodoData;
use App\Enums\EstadoDocumento;
use App\Enums\EstadoEvento;
use App\Enums\EstadoExpediente;
use App\Enums\TipoEvento;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Expediente;
use Carbon\CarbonInterface;

class DashboardMetricsService
{
    public function __construct(
        private readonly PeriodoService $periodos,
    ) {}

    /**
     * @return array{
     *     periodo: PeriodoData,
     *     casos_activos: array{valor: int, variacion: float|null},
     *     audiencias_hoy: array{valor: int, variacion: float|null},
     *     clientes_totales: array{valor: int, variacion: float|null},
     *     documentos_pendientes: array{valor: int, variacion: float|null}
     * }
     */
    public function resumen(CarbonInterface|string|null $fecha = null): array
    {
        $periodo = $this->periodos->para($fecha);
        $fechaRef = $periodo->fechaReferencia;

        $casosActual = $this->casosActivosHasta($periodo->actualFin);
        $casosAnterior = $this->casosActivosHasta($periodo->anteriorFin);

        $audienciasHoy = $this->audienciasEnFecha($fechaRef);
        $audienciasMesAnterior = $this->audienciasEnFecha($fechaRef->subMonthNoOverflow());

        $clientesActual = $this->clientesHasta($periodo->actualFin);
        $clientesAnterior = $this->clientesHasta($periodo->anteriorFin);

        $docsActual = $this->documentosPendientesHasta($periodo->actualFin);
        $docsAnterior = $this->documentosPendientesHasta($periodo->anteriorFin);

        return [
            'periodo' => $periodo,
            'casos_activos' => [
                'valor' => $casosActual,
                'variacion' => $this->variacionPorcentual($casosActual, $casosAnterior),
            ],
            'audiencias_hoy' => [
                'valor' => $audienciasHoy,
                'variacion' => $this->variacionPorcentual($audienciasHoy, $audienciasMesAnterior),
            ],
            'clientes_totales' => [
                'valor' => $clientesActual,
                'variacion' => $this->variacionPorcentual($clientesActual, $clientesAnterior),
            ],
            'documentos_pendientes' => [
                'valor' => $docsActual,
                'variacion' => $this->variacionPorcentual($docsActual, $docsAnterior),
            ],
        ];
    }

    public function casosActivosHasta(CarbonInterface $hasta): int
    {
        return Expediente::query()
            ->where('created_at', '<=', $hasta)
            ->where('estado', '!=', EstadoExpediente::Cerrado)
            ->count();
    }

    public function audienciasEnFecha(CarbonInterface $fecha): int
    {
        return Evento::query()
            ->where('tipo', TipoEvento::Audiencia)
            ->where('estado', '!=', EstadoEvento::Cancelada)
            ->whereDate('inicio', $fecha->toDateString())
            ->count();
    }

    public function clientesHasta(CarbonInterface $hasta): int
    {
        return Cliente::query()
            ->where('created_at', '<=', $hasta)
            ->count();
    }

    public function documentosPendientesHasta(CarbonInterface $hasta): int
    {
        return Documento::query()
            ->where('created_at', '<=', $hasta)
            ->whereIn('estado', [
                EstadoDocumento::PendienteRevision,
                EstadoDocumento::PendienteFirma,
                EstadoDocumento::PendienteAprobacion,
            ])
            ->count();
    }

    public function variacionPorcentual(int $actual, int $anterior): ?float
    {
        if ($anterior <= 0) {
            return null;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }
}
