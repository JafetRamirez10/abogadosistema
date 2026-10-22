<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\DocumentoVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoDescargaController extends Controller
{
    public function firmar(Request $request, Documento $documento): JsonResponse|RedirectResponse
    {
        $this->authorize('download', $documento);

        $version = $documento->ultimaVersion;
        abort_if($version === null, 404, 'El documento no tiene versiones.');

        $url = URL::temporarySignedRoute(
            'documentos.descargar',
            now()->addMinutes(5),
            [
                'documento' => $documento->getKey(),
                'version' => $version->getKey(),
            ],
        );

        if ($request->expectsJson()) {
            return response()->json(['url' => $url]);
        }

        return redirect()->away($url);
    }

    public function descargar(Request $request, Documento $documento, DocumentoVersion $version): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        $this->authorize('download', $documento);

        abort_unless((int) $version->documento_id === (int) $documento->getKey(), 404);

        $disk = Storage::disk($version->disco);
        abort_unless($disk->exists($version->ruta), 404);

        $nombre = $documento->nombre;
        $extension = pathinfo($version->ruta, PATHINFO_EXTENSION);
        if ($extension !== '' && ! str_ends_with(strtolower($nombre), '.'.strtolower($extension))) {
            $nombre .= '.'.$extension;
        }

        return $disk->download($version->ruta, $nombre, [
            'Content-Type' => $version->mime,
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }
}
