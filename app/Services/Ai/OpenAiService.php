<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\BorradorIaData;
use App\Models\Expediente;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class OpenAiService extends AbstractAiService
{
    public function resumirExpediente(Expediente $expediente): string
    {
        $user = $this->mensajeUsuarioResumen($expediente);

        return $this->completar(self::SYSTEM_PROMPT, $user);
    }

    public function borradorEscrito(Expediente $expediente, BorradorIaData $data): string
    {
        $user = $this->mensajeUsuarioBorrador($expediente, $data);

        return $this->completar(self::SYSTEM_PROMPT, $user);
    }

    private function completar(string $system, string $user): string
    {
        $this->ultimoTokens = null;

        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('La clave de OpenAI no está configurada.');
        }

        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        try {
            $response = Http::withToken($key)
                ->timeout(30)
                ->retry(2, 200, throw: false)
                ->acceptJson()
                ->asJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.3,
                ]);
        } catch (ConnectionException $e) {
            // Nunca registrar la API key.
            Log::warning('OpenAI: error de conexión', [
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('No se pudo conectar con el servicio de IA. Intentá de nuevo.', 0, $e);
        }

        if (! $response->successful()) {
            Log::warning('OpenAI: respuesta no exitosa', [
                'status' => $response->status(),
            ]);

            throw new RuntimeException('El servicio de IA no está disponible en este momento.');
        }

        /** @var array{choices?: list<array{message?: array{content?: string}}>, usage?: array{total_tokens?: int}} $json */
        $json = $response->json();

        $contenido = $json['choices'][0]['message']['content'] ?? null;
        if (! is_string($contenido) || trim($contenido) === '') {
            throw new RuntimeException('La IA no devolvió contenido útil.');
        }

        $tokens = $json['usage']['total_tokens'] ?? null;
        $this->ultimoTokens = is_int($tokens) ? $tokens : null;

        return trim($contenido);
    }
}
