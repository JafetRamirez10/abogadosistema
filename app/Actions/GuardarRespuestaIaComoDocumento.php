<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoDocumento;
use App\Enums\TipoActuacion;
use App\Models\Actuacion;
use App\Models\Documento;
use App\Models\DocumentoVersion;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

final class GuardarRespuestaIaComoDocumento
{
    public function handle(
        Expediente $expediente,
        User $user,
        string $contenido,
        string $nombre,
    ): Documento {
        Gate::forUser($user)->authorize('create', Documento::class);

        $nombre = trim($nombre) !== '' ? trim($nombre) : 'Borrador IA';
        $uuid = (string) Str::uuid();
        $ruta = sprintf(
            'despachos/%d/expedientes/%d/%s.docx',
            $expediente->despacho_id,
            $expediente->getKey(),
            $uuid,
        );

        $binario = $this->generarDocx($contenido);
        Storage::disk('local')->put($ruta, $binario);

        return DB::transaction(function () use ($expediente, $user, $nombre, $ruta, $binario): Documento {
            $documento = Documento::query()->create([
                'despacho_id' => $expediente->despacho_id,
                'expediente_id' => $expediente->getKey(),
                'nombre' => $nombre.' (DOCX)',
                'version_actual' => 1,
                'estado' => EstadoDocumento::PendienteRevision,
            ]);

            DocumentoVersion::query()->create([
                'despacho_id' => $expediente->despacho_id,
                'documento_id' => $documento->getKey(),
                'version' => 1,
                'ruta' => $ruta,
                'disco' => 'local',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'tamano' => strlen($binario),
                'sha256' => hash('sha256', $binario),
                'user_id' => $user->getKey(),
                'nota' => 'Guardado desde respuesta de IA (borrador para revisión)',
            ]);

            Actuacion::query()->create([
                'despacho_id' => $expediente->despacho_id,
                'expediente_id' => $expediente->getKey(),
                'user_id' => $user->getKey(),
                'fecha' => now('America/Costa_Rica')->toDateString(),
                'tipo' => TipoActuacion::Escrito,
                'descripcion' => 'Documento guardado desde IA: '.$documento->nombre,
                'documento_id' => $documento->getKey(),
            ]);

            return $documento->fresh(['versiones', 'ultimaVersion']) ?? $documento;
        });
    }

    private function generarDocx(string $textoPlano): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        foreach (preg_split("/\r\n|\n|\r/", $textoPlano) ?: [$textoPlano] as $linea) {
            $section->addText(
                htmlspecialchars($linea, ENT_QUOTES | ENT_XML1, 'UTF-8'),
                ['name' => 'Times New Roman', 'size' => 12],
            );
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ia-docx-');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal para el documento.');
        }

        $path = $tmp.'.docx';
        @unlink($tmp);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        $contenido = file_get_contents($path);
        @unlink($path);

        if ($contenido === false) {
            throw new \RuntimeException('No se pudo generar el documento DOCX.');
        }

        return $contenido;
    }
}
