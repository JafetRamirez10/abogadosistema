<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\BorradorIaData;
use App\Models\Expediente;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class DeepSeekService extends AbstractAiService
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

        $key = config('services.deepseek.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('La clave de DeepSeek no está configurada.');
        }

        $base = rtrim((string) config('services.deepseek.base_url', 'https://api.deepseek.com'), '/');
        $model = (string) config('services.deepseek.model', 'deepseek-chat');

        try {
            $response = Http::withToken($key)
                ->timeout(60)
                ->retry(2, 200, throw: false)
                ->acceptJson()
                ->asJson()
                ->post($base.'/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'temperature' => 0.3,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('DeepSeek: error de conexión', [
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('No se pudo conectar con DeepSeek. Intentá de nuevo.', 0, $e);
        }

        if (! $response->successful()) {
            Log::warning('DeepSeek: respuesta no exitosa', [
                'status' => $response->status(),
            ]);

            throw new RuntimeException('El servicio de IA (DeepSeek) no está disponible en este momento.');
        }

        /** @var array{choices?: list<array{message?: array{content?: string}}>, usage?: array{total_tokens?: int}} $json */
        $json = $response->json();

        $contenido = $json['choices'][0]['message']['content'] ?? null;
        if (! is_string($contenido) || trim($contenido) === '') {
            throw new RuntimeException('DeepSeek no devolvió contenido útil.');
        }

        $tokens = $json['usage']['total_tokens'] ?? null;
        $this->ultimoTokens = is_int($tokens) ? $tokens : null;

        return trim($contenido);
    }

    public function completarChatConHerramientas(array $messages, array $tools): array
    {
        $this->ultimoTokens = null;

        $key = config('services.deepseek.key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('La clave de DeepSeek no está configurada.');
        }

        $base = rtrim((string) config('services.deepseek.base_url', 'https://api.deepseek.com'), '/');
        $model = (string) config('services.deepseek.model', 'deepseek-chat');

        try {
            $response = Http::withToken($key)
                ->timeout(90)
                ->retry(2, 200, throw: false)
                ->acceptJson()
                ->asJson()
                ->post($base.'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                    'temperature' => 0.2,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('DeepSeek tools: error de conexión', ['message' => $e->getMessage()]);
            throw new RuntimeException('No se pudo conectar con DeepSeek. Intentá de nuevo.', 0, $e);
        }

        if (! $response->successful()) {
            Log::warning('DeepSeek tools: respuesta no exitosa', ['status' => $response->status()]);
            throw new RuntimeException('DeepSeek no está disponible en este momento.');
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
