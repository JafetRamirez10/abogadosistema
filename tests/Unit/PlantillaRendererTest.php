<?php

declare(strict_types=1);

use App\DTOs\VariablesPlantillaData;
use App\Support\PlantillaRenderer;

it('escapa etiquetas script al renderizar para PDF', function (): void {
    $renderer = new PlantillaRenderer;
    $variables = new VariablesPlantillaData([
        'cliente.nombre' => '<script>alert(1)</script>',
        'cliente.cedula' => '1-2345-6789',
        'cliente.direccion' => '',
        'cliente.tipo' => 'Persona física',
        'expediente.numero_interno' => 'EXP-1',
        'expediente.numero_judicial' => '',
        'expediente.titulo' => 'Caso',
        'expediente.materia' => 'Civil',
        'expediente.juzgado' => '',
        'abogado.nombre' => 'Abogado',
        'despacho.nombre' => 'Despacho',
        'contraparte.nombre' => '',
        'fecha_hoy' => '21/10/2026',
    ]);

    $resultado = $renderer->render(
        'Cliente: {{ cliente.nombre }}',
        $variables,
        escapeHtml: true,
    );

    expect($resultado)->not->toContain('<script>')
        ->and($resultado)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('marca variables desconocidas y respeta la lista blanca', function (): void {
    $renderer = new PlantillaRenderer;
    $variables = new VariablesPlantillaData([
        'cliente.nombre' => 'Juan',
    ]);

    $resultado = $renderer->render(
        'Hola {{ cliente.nombre }} y {{ secreto.interno }}',
        $variables,
    );

    expect($resultado)->toBe('Hola Juan y [[variable no encontrada]]');
});
