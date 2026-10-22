<?php

declare(strict_types=1);

namespace App\Support;

use App\DTOs\VariablesPlantillaData;

class PlantillaRenderer
{
    /** @var list<string> */
    public const VARIABLES_PERMITIDAS = [
        'cliente.nombre',
        'cliente.cedula',
        'cliente.direccion',
        'cliente.tipo',
        'expediente.numero_interno',
        'expediente.numero_judicial',
        'expediente.titulo',
        'expediente.materia',
        'expediente.juzgado',
        'abogado.nombre',
        'despacho.nombre',
        'contraparte.nombre',
        'fecha_hoy',
    ];

    private const PATTERN = '/\{\{\s*([a-z_\.]+)\s*\}\}/';

    public function render(string $contenido, VariablesPlantillaData $variables, bool $escapeHtml = false): string
    {
        $map = $variables->toArray();
        $permitidas = array_flip(self::VARIABLES_PERMITIDAS);

        return (string) preg_replace_callback(
            self::PATTERN,
            static function (array $matches) use ($map, $permitidas, $escapeHtml): string {
                $key = $matches[1];

                if (! isset($permitidas[$key])) {
                    return '[[variable no encontrada]]';
                }

                $value = $map[$key] ?? '[[variable no encontrada]]';

                return $escapeHtml ? e($value) : $value;
            },
            $contenido,
        );
    }
}
