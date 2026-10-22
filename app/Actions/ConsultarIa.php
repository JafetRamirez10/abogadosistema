<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\AiService;
use App\DTOs\BorradorIaData;
use App\Enums\TipoConsultaIa;
use App\Models\AiConsulta;
use App\Models\Expediente;
use App\Models\User;
use App\Services\Ai\AbstractAiService;
use App\Services\Ai\AiContextBuilder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class ConsultarIa
{
    public function __construct(
        private readonly AiService $ai,
        private readonly AiContextBuilder $contextBuilder,
    ) {}

    public function resumir(Expediente $expediente, User $user): AiConsulta
    {
        $this->autorizar($expediente, $user);
        $this->ensureRateLimits($user, (int) $expediente->despacho_id);

        $userMessage = $this->mensajeResumen($expediente);
        $promptHash = hash('sha256', AbstractAiService::SYSTEM_PROMPT."\n".$userMessage);

        $respuesta = $this->ai->resumirExpediente($expediente);
        $this->hitRateLimits($user, (int) $expediente->despacho_id);

        return $this->persistir(
            expediente: $expediente,
            user: $user,
            tipo: TipoConsultaIa::Resumen,
            promptHash: $promptHash,
            respuesta: $respuesta,
        );
    }

    public function borrador(Expediente $expediente, User $user, BorradorIaData $data): AiConsulta
    {
        $this->autorizar($expediente, $user);
        $this->ensureRateLimits($user, (int) $expediente->despacho_id);

        $userMessage = $this->mensajeBorrador($expediente, $data);
        $promptHash = hash('sha256', AbstractAiService::SYSTEM_PROMPT."\n".$userMessage);

        $respuesta = $this->ai->borradorEscrito($expediente, $data);
        $this->hitRateLimits($user, (int) $expediente->despacho_id);

        return $this->persistir(
            expediente: $expediente,
            user: $user,
            tipo: TipoConsultaIa::Borrador,
            promptHash: $promptHash,
            respuesta: $respuesta,
        );
    }

    private function autorizar(Expediente $expediente, User $user): void
    {
        Gate::forUser($user)->authorize('view', $expediente);

        if (! $user->can('usar_ia')) {
            abort(403, 'No tenés permiso para usar el asistente de IA.');
        }
    }

    private function ensureRateLimits(User $user, int $despachoId): void
    {
        $userKey = $this->userLimitKey($user);
        $despachoKey = $this->despachoLimitKey($despachoId);

        if (RateLimiter::tooManyAttempts($userKey, 10) || RateLimiter::tooManyAttempts($despachoKey, 200)) {
            throw new TooManyRequestsHttpException(
                retryAfter: RateLimiter::availableIn($userKey) ?: RateLimiter::availableIn($despachoKey),
                message: 'Has alcanzado el límite de consultas de IA. Intentá más tarde.',
            );
        }
    }

    private function hitRateLimits(User $user, int $despachoId): void
    {
        RateLimiter::hit($this->userLimitKey($user), 60);
        RateLimiter::hit($this->despachoLimitKey($despachoId), 86_400);
    }

    private function userLimitKey(User $user): string
    {
        return 'ia:user:'.$user->getAuthIdentifier();
    }

    private function despachoLimitKey(int $despachoId): string
    {
        return 'ia:despacho:'.$despachoId;
    }

    private function persistir(
        Expediente $expediente,
        User $user,
        TipoConsultaIa $tipo,
        string $promptHash,
        string $respuesta,
    ): AiConsulta {
        return AiConsulta::query()->create([
            'despacho_id' => $expediente->despacho_id,
            'expediente_id' => $expediente->getKey(),
            'user_id' => $user->getKey(),
            'tipo' => $tipo,
            'prompt_hash' => $promptHash,
            'respuesta' => $respuesta,
            'tokens' => $this->ai->ultimoTokens(),
        ]);
    }

    private function mensajeResumen(Expediente $expediente): string
    {
        $datos = $this->contextBuilder->build($expediente);

        return "Generá un resumen claro del expediente para el abogado.\n\n<datos>\n{$datos}\n</datos>";
    }

    private function mensajeBorrador(Expediente $expediente, BorradorIaData $data): string
    {
        $datos = $this->contextBuilder->build($expediente);
        $instrucciones = mb_substr($data->instrucciones, 0, 2000);
        $tono = filled($data->tono) ? "\nTono: {$data->tono}" : '';

        return "Redactá un borrador de escrito tipo «{$data->tipoEscrito->label()}»."
            ."{$tono}\nInstrucciones del abogado:\n{$instrucciones}\n\n"
            ."<datos>\n{$datos}\n</datos>";
    }
}
