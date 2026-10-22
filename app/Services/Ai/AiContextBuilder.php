<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Actuacion;
use App\Models\Expediente;
use App\Models\Parte;

/**
 * Construye contexto sanitizado para IA: sin cédulas, teléfonos, emails,
 * direcciones ni montos.
 */
final class AiContextBuilder
{
    public function build(Expediente $expediente): string
    {
        $expediente->loadMissing(['partes', 'actuaciones']);

        $lineas = [
            'Materia: '.$expediente->materia->label(),
            'Estado: '.$expediente->estado->label(),
            'Juzgado: '.($expediente->juzgado ?: '(no indicado)'),
            '',
            'Partes:',
        ];

        $partes = $expediente->partes
            ->sortBy(fn (Parte $parte): string => $parte->rol->value)
            ->values();

        if ($partes->isEmpty()) {
            $lineas[] = '- (sin partes registradas)';
        } else {
            foreach ($partes as $parte) {
                $nombre = filled($parte->nombre) ? $parte->nombre : '(sin nombre)';
                $lineas[] = '- '.$parte->rol->label().': '.$nombre;
            }
        }

        $lineas[] = '';
        $lineas[] = 'Últimas actuaciones (máx. 20):';

        $actuaciones = $expediente->actuaciones
            ->sortByDesc(fn (Actuacion $a): string => $a->fecha->format('Y-m-d'))
            ->take(20)
            ->values();

        if ($actuaciones->isEmpty()) {
            $lineas[] = '- (sin actuaciones)';
        } else {
            foreach ($actuaciones as $actuacion) {
                $descripcion = $this->sanitizarTexto((string) $actuacion->descripcion);
                $lineas[] = sprintf(
                    '- [%s] %s: %s',
                    $actuacion->fecha->format('Y-m-d'),
                    $actuacion->tipo->label(),
                    $descripcion,
                );
            }
        }

        return implode("\n", $lineas);
    }

    private function sanitizarTexto(string $texto): string
    {
        // Evita filtrar montos/correos/teléfonos que pudieran colarse en notas libres.
        $texto = preg_replace('/\b[\w.+-]+@[\w-]+\.[\w.-]+\b/u', '[email omitido]', $texto) ?? $texto;
        $texto = preg_replace('/\b(?:\+?506[-\s]?)?\d{4}[-\s]?\d{4}\b/u', '[teléfono omitido]', $texto) ?? $texto;
        $texto = preg_replace('/\b\d{1,3}(?:[.,]\d{3})+(?:[.,]\d{2})?\b/u', '[monto omitido]', $texto) ?? $texto;
        $texto = preg_replace('/\b\d{9,12}\b/u', '[dato omitido]', $texto) ?? $texto;

        return $texto;
    }
}
