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
        return $this->completarChat(self::SYSTEM_PROMPT, $this->mensajeUsuarioResumen($expediente));
    }

    public function borradorEscrito(Expediente $expediente, BorradorIaData $data): string
    {
        return $this->completarChat(self::SYSTEM_PROMPT, $this->mensajeUsuarioBorrador($expediente, $data));
    }

    public function completarChat(string $systemPrompt, string $userMessage): string
    {
        $this->ultimoTokens = null;

        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('La clave de OpenAI no está configurada.');
        }

        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        try {
            $response = Http::withToken($key)
                ->timeout(60)
                ->retry(2, 200, throw: false)
                ->acceptJson()
                ->asJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
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

    public function completarChatConHerramientas(array $messages, array $tools): array
    {
        $this->ultimoTokens = null;

        $key = config('services.openai.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('La clave de OpenAI no está configurada.');
        }

        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        try {
            $response = Http::withToken($key)
                ->timeout(90)
                ->retry(2, 200, throw: false)
                ->acceptJson()
                ->asJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                    'temperature' => 0.2,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenAI tools: error de conexión', ['message' => $e->getMessage()]);
            throw new RuntimeException('No se pudo conectar con el servicio de IA. Intentá de nuevo.', 0, $e);
        }

        if (! $response->successful()) {
            Log::warning('OpenAI tools: respuesta no exitosa', ['status' => $response->status()]);
            throw new RuntimeException('El servicio de IA no está disponible en este momento.');
        }

        /** @var array{choices?: list<array{message?: array{content?: string|null, tool_calls?: list<array{id?: string, function?: array{name?: string, arguments?: string}}>}}>, usage?: array{total_tokens?: int}} $json */
        $json = $response->json();
        $message = $json['choices'][0]['message'] ?? [];
        $content = $message['content'] ?? null;
        $rawCalls = $message['tool_calls'] ?? [];

        $toolCalls = [];
        foreach ($rawCalls as $call) {
            $name = $call['function']['name'] ?? null;
            $id = $call['id'] ?? uniqid('call_', true);
            $argsRaw = $call['function']['arguments'] ?? '{}';
            $args = json_decode(is_string($argsRaw) ? $argsRaw : '{}', true);
            if (! is_string($name) || $name === '') {
                continue;
            }
            $toolCalls[] = [
                'id' => (string) $id,
                'name' => $name,
                'arguments' => is_array($args) ? $args : [],
            ];
        }

        $tokens = $json['usage']['total_tokens'] ?? null;
        $this->ultimoTokens = is_int($tokens) ? $tokens : null;

        return [
            'content' => is_string($content) ? trim($content) : null,
            'tool_calls' => $toolCalls,
            'tokens' => $this->ultimoTokens,
        ];
    }
}
