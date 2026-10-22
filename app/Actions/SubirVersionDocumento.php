<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EstadoDocumento;
use App\Models\Documento;
use App\Models\DocumentoVersion;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubirVersionDocumento
{
    /**
     * @param  array{nombre?: string, nota?: string|null, estado?: EstadoDocumento|string|null}  $meta
     */
    public function handle(
        Expediente $expediente,
        UploadedFile $archivo,
        User $user,
        ?Documento $documento = null,
        array $meta = [],
    ): Documento {
        Gate::forUser($user)->authorize(
            $documento === null ? 'create' : 'update',
            $documento ?? Documento::class,
        );

        $validator = Validator::make(
            [
                'archivo' => $archivo,
                'nombre' => $meta['nombre'] ?? $archivo->getClientOriginalName(),
                'nota' => $meta['nota'] ?? null,
            ],
            [
                'archivo' => [
                    'required',
                    'file',
                    'max:10240',
                    'mimes:pdf,doc,docx,jpg,jpeg,png',
                    'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png',
                ],
                'nombre' => ['required', 'string', 'max:255'],
                'nota' => ['nullable', 'string', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $extension = strtolower($archivo->getClientOriginalExtension() ?: 'bin');
        $uuid = (string) Str::uuid();
        $ruta = sprintf(
            'despachos/%d/expedientes/%d/%s.%s',
            $expediente->despacho_id,
            $expediente->getKey(),
            $uuid,
            $extension,
        );

        $contenido = file_get_contents($archivo->getRealPath());
        if ($contenido === false) {
            throw ValidationException::withMessages([
                'archivo' => 'No se pudo leer el archivo subido.',
            ]);
        }

        Storage::disk('local')->put($ruta, $contenido);

        $sha256 = hash('sha256', $contenido);
        $mime = $archivo->getMimeType() ?: 'application/octet-stream';
        $tamano = $archivo->getSize() ?: strlen($contenido);

        return DB::transaction(function () use ($expediente, $documento, $user, $meta, $ruta, $mime, $tamano, $sha256): Documento {
            if ($documento === null) {
                $documento = Documento::query()->create([
                    'despacho_id' => $expediente->despacho_id,
                    'expediente_id' => $expediente->getKey(),
                    'nombre' => $meta['nombre'] ?? 'Documento',
                    'version_actual' => 1,
                    'estado' => $meta['estado'] ?? EstadoDocumento::PendienteRevision,
                ]);
                $version = 1;
            } else {
                $version = ((int) $documento->version_actual) + 1;
                $documento->update([
                    'version_actual' => $version,
                    'nombre' => $meta['nombre'] ?? $documento->nombre,
                    'estado' => $meta['estado'] ?? $documento->estado,
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
                'nota' => $meta['nota'] ?? null,
            ]);

            return $documento->fresh(['versiones', 'ultimaVersion']) ?? $documento;
        });
    }
}
