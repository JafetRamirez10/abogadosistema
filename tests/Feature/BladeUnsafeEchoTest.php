<?php

declare(strict_types=1);

/**
 * CI: evita echo sin escape en vistas propias.
 * Permitido solo nl2br(e(...)) en una sola línea.
 */
it('no usa echo sin escape injustificado en resources/views', function (): void {
    $root = resource_path('views');
    $violaciones = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $lineas = file($file->getPathname(), FILE_IGNORE_NEW_LINES);
        if ($lineas === false) {
            continue;
        }

        foreach ($lineas as $numero => $linea) {
            // Ignorar comentarios Blade de una línea.
            if (str_contains($linea, '{{--')) {
                continue;
            }

            if (! preg_match_all('/\{!!\s*(.+?)\s*!!\}/', $linea, $matches)) {
                continue;
            }

            foreach ($matches[1] as $expr) {
                $exprNormalizado = preg_replace('/\s+/', '', $expr) ?? $expr;
                if (preg_match('/^nl2br\(e\(.+\)\)$/', $exprNormalizado) === 1) {
                    continue;
                }

                $relativo = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                $violaciones[] = $relativo.':'.($numero + 1).': {!! '.$expr.' !!}';
            }
        }
    }

    expect($violaciones)->toBeEmpty(
        "Vistas con echo sin escape injustificado:\n".implode("\n", $violaciones),
    );
});
