<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoDocumento;
use App\Enums\FormatoSalidaPlantilla;
use App\Enums\TipoActuacion;
use App\Models\Actuacion;
use App\Models\Documento;
use App\Models\DocumentoVersion;
use App\Models\Expediente;
use App\Models\Plantilla;
use App\Models\User;
use App\Support\PlantillaRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class GenerarDocumentoDesdePlantilla
{
    public function __construct(
        private readonly ConstruirVariablesPlantilla $construirVariables,
        private readonly PlantillaRenderer $renderer,
    ) {}

    /**
     * @return list<Documento>
     */
    public function handle(
        Expediente $expediente,
        Plantilla $plantilla,
        User $user,
        ?Documento $documento = null,
        ?string $nombre = null,
    ): array {
        Gate::forUser($user)->authorize(
            $documento === null ? 'create' : 'update',
            $documento ?? Documento::class,
        );

        $variables = ($this->construirVariables)($expediente, $user);
        $textoPlano = $this->renderer->render($plantilla->contenido, $variables, escapeHtml: false);

        $formatos = match ($plantilla->formato_salida) {
            FormatoSalidaPlantilla::Docx => ['docx'],
            FormatoSalidaPlantilla::Pdf => ['pdf'],
            FormatoSalidaPlantilla::Ambos => ['docx', 'pdf'],
        };

        $documentos = [];

        foreach ($formatos as $formato) {
            $documentos[] = $this->persistirFormato(
                expediente: $expediente,
                plantilla: $plantilla,
                user: $user,
                documento: $documento,
                nombre: $nombre ?? $plantilla->nombre,
                formato: $formato,
                textoPlano: $textoPlano,
            );
            // Si se pasa un documento existente, solo la primera iteración lo versiona;
            // formatos adicionales crean documentos hermanos.
            $documento = null;
        }

        return $documentos;
    }

    private function persistirFormato(
        Expediente $expediente,
        Plantilla $plantilla,
        User $user,
        ?Documento $documento,
        string $nombre,
        string $formato,
        string $textoPlano,
    ): Documento {
        $uuid = (string) Str::uuid();
        $ruta = sprintf(
            'despachos/%d/expedientes/%d/%s.%s',
            $expediente->despacho_id,
            $expediente->getKey(),
            $uuid,
            $formato,
        );

        if ($formato === 'pdf') {
            $contenido = $this->generarPdf($textoPlano);
            $mime = 'application/pdf';
        } else {
            $contenido = $this->generarDocx($textoPlano);
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        Storage::disk('local')->put($ruta, $contenido);
        $sha256 = hash('sha256', $contenido);
        $tamano = strlen($contenido);
        $nombreFinal = $nombre.($formato === 'pdf' ? ' (PDF)' : ' (DOCX)');

        return DB::transaction(function () use (
            $expediente,
            $plantilla,
            $user,
            $documento,
            $nombreFinal,
            $ruta,
            $mime,
            $tamano,
            $sha256,
        ): Documento {
            if ($documento === null) {
                $documento = Documento::query()->create([
                    'despacho_id' => $expediente->despacho_id,
                    'expediente_id' => $expediente->getKey(),
                    'nombre' => $nombreFinal,
                    'plantilla_id' => $plantilla->getKey(),
                    'version_actual' => 1,
                    'estado' => EstadoDocumento::PendienteRevision,
                ]);
                $version = 1;
            } else {
                $version = ((int) $documento->version_actual) + 1;
                $documento->update([
                    'version_actual' => $version,
                    'plantilla_id' => $plantilla->getKey(),
                    'nombre' => $nombreFinal,
                ]);
            }

            DocumentoVersion::query()->create([
                'despacho_id' => $expediente->despacho_id,
                'documento_id' => $documento->getKey(),
                'version' => $version,
                'ruta' => $ruta,
                'disco' => 'local',
                'mime' => $mime,
                'tamano' => $tamano,
                'sha256' => $sha256,
                'user_id' => $user->getKey(),
                'nota' => 'Generado desde plantilla: '.$plantilla->nombre,
            ]);

            Actuacion::query()->create([
                'despacho_id' => $expediente->despacho_id,
                'expediente_id' => $expediente->getKey(),
                'user_id' => $user->getKey(),
                'fecha' => now('America/Costa_Rica')->toDateString(),
                'tipo' => TipoActuacion::Escrito,
                'descripcion' => 'Documento generado desde plantilla «'.$plantilla->nombre.'»: '.$nombreFinal,
                'documento_id' => $documento->getKey(),
            ]);

            return $documento->fresh(['versiones', 'ultimaVersion']) ?? $documento;
        });
    }

    private function generarPdf(string $textoPlano): string
    {
        $html = view('documentos.plantilla-pdf', [
            'contenido' => $textoPlano,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setOption(['isRemoteEnabled' => false]);

        return $pdf->output();
    }

    private function generarDocx(string $textoPlano): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $parrafos = preg_split("/\r\n|\n|\r/", $textoPlano) ?: [$textoPlano];
        foreach ($parrafos as $parrafo) {
            $section->addText($parrafo === '' ? ' ' : htmlspecialchars($parrafo, ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }

        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal para DOCX.');
        }

        $path = $tmp.'.docx';
        rename($tmp, $path);

        try {
            IOFactory::createWriter($phpWord, 'Word2007')->save($path);
            $contenido = file_get_contents($path);
            if ($contenido === false) {
                throw new \RuntimeException('No se pudo leer el DOCX generado.');
            }

            return $contenido;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
