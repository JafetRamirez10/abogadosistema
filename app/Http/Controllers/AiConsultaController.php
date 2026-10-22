<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConsultarIa;
use App\DTOs\BorradorIaData;
use App\Enums\TipoEscritoIa;
use App\Models\Expediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class AiConsultaController extends Controller
{
    public function resumir(Request $request, Expediente $expediente, ConsultarIa $consultarIa): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        try {
            $consulta = $consultarIa->resumir($expediente, $user);
        } catch (TooManyRequestsHttpException $e) {
            throw $e;
        } catch (Throwable) {
            return response()->json([
                'message' => 'No se pudo generar el resumen. Intentá de nuevo más tarde.',
            ], 502);
        }

        return response()->json([
            'id' => $consulta->getKey(),
            'tipo' => $consulta->tipo->value,
            'respuesta' => $consulta->respuesta,
            'aviso' => 'Borrador generado por IA, revisalo antes de usarlo.',
        ]);
    }

    public function borrador(Request $request, Expediente $expediente, ConsultarIa $consultarIa): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $validated = $request->validate([
            'instrucciones' => ['required', 'string', 'max:2000'],
            'tipo_escrito' => ['required', Rule::enum(TipoEscritoIa::class)],
            'tono' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $consulta = $consultarIa->borrador(
                $expediente,
                $user,
                BorradorIaData::fromArray($validated),
            );
        } catch (TooManyRequestsHttpException $e) {
            throw $e;
        } catch (Throwable) {
            return response()->json([
                'message' => 'No se pudo generar el borrador. Intentá de nuevo más tarde.',
            ], 502);
        }

        return response()->json([
            'id' => $consulta->getKey(),
            'tipo' => $consulta->tipo->value,
            'respuesta' => $consulta->respuesta,
            'aviso' => 'Borrador generado por IA, revisalo antes de usarlo.',
        ]);
    }
}
