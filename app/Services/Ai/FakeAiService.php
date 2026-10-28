<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\BorradorIaData;
use App\Models\Expediente;

final class FakeAiService extends AbstractAiService
{
    public function resumirExpediente(Expediente $expediente): string
    {
        $this->ultimoTokens = null;
        $contexto = $this->contextBuilder->build($expediente);

        return "Resumen simulado (FakeAiService) del expediente {$expediente->numero_interno}.\n\n"
            ."Este es un borrador para revisión del abogado.\n\n"
            ."Contexto utilizado:\n{$contexto}";
    }

    public function borradorEscrito(Expediente $expediente, BorradorIaData $data): string
    {
        $this->ultimoTokens = null;
        $contexto = $this->contextBuilder->build($expediente);

        return "Borrador simulado (FakeAiService) — {$data->tipoEscrito->label()}.\n\n"
            ."Expediente: {$expediente->numero_interno}\n"
            ."Instrucciones: {$data->instrucciones}\n\n"
            ."Este es un borrador para revisión del abogado. No cite leyes inventadas.\n\n"
            ."Contexto:\n{$contexto}";
    }

    public function completarChat(string $systemPrompt, string $userMessage): string
    {
        $this->ultimoTokens = 42;

        return "Respuesta simulada (FakeAiService / DeepSeek no configurado).\n\n"
            ."Pregunta recibida:\n".mb_substr($userMessage, 0, 800)."\n\n"
            ."Esto es un borrador para revisión del abogado. No sustituye dictamen profesional.";
    }

    public function completarChatConHerramientas(array $messages, array $tools): array
    {
        $this->ultimoTokens = 42;

        $lastUser = '';
        foreach (array_reverse($messages) as $msg) {
            if (($msg['role'] ?? '') === 'user' && is_string($msg['content'] ?? null)) {
                $lastUser = (string) $msg['content'];
                break;
            }
        }

        // El prompt del agente incluye corpus; tomar solo el pedido del abogado.
        if (preg_match('/Pedido del abogado:\s*(.+?)(?:\n\n<corpus>|\n\n<datos>|$)/is', $lastUser, $pedido)) {
            $lastUser = trim($pedido[1]);
        } elseif (preg_match('/Pregunta del abogado:\s*(.+?)(?:\n\n<corpus>|\n\n<datos>|$)/is', $lastUser, $pedido)) {
            $lastUser = trim($pedido[1]);
        }

        $lower = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($lastUser));

        // Si ya hay resultados de herramientas, devolver texto final.
        foreach (array_reverse($messages) as $msg) {
            if (($msg['role'] ?? '') === 'tool') {
                return [
                    'content' => 'Listo (FakeAiService). Resultado de herramientas procesado: '
                        .mb_substr((string) ($msg['content'] ?? ''), 0, 400),
                    'tool_calls' => [],
                    'tokens' => 42,
                ];
            }
        }

        if (str_contains($lower, 'eliminar tarea') || str_contains($lower, 'borra la tarea') || str_contains($lower, 'elimina la tarea')) {
            if (preg_match('/\b(\d+)\b/', $lastUser, $m)) {
                return [
                    'content' => null,
                    'tool_calls' => [[
                        'id' => 'fake_del_tarea',
                        'name' => 'eliminar_tarea',
                        'arguments' => ['tarea_id' => (int) $m[1]],
                    ]],
                    'tokens' => 42,
                ];
            }
        }

        if (str_contains($lower, 'actualizar tarea') || str_contains($lower, 'modifica la tarea') || str_contains($lower, 'cambia la tarea')) {
            if (preg_match('/\b(\d+)\b/', $lastUser, $m)) {
                return [
                    'content' => null,
                    'tool_calls' => [[
                        'id' => 'fake_upd_tarea',
                        'name' => 'actualizar_tarea',
                        'arguments' => [
                            'tarea_id' => (int) $m[1],
                            'prioridad' => 'alta',
                        ],
                    ]],
                    'tokens' => 42,
                ];
            }
        }

        if (str_contains($lower, 'crear tarea') || str_contains($lower, 'crea una tarea') || str_contains($lower, 'nueva tarea') || str_contains($lower, 'crea una tarea')) {
            $titulo = 'Tarea creada por IA';
            if (preg_match('/tarea[:\s]+(.+)$/iu', $lastUser, $m)) {
                $titulo = mb_substr(trim($m[1]), 0, 120);
            }

            return [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'fake_crear_tarea',
                    'name' => 'crear_tarea',
                    'arguments' => [
                        'titulo' => $titulo !== '' ? $titulo : 'Tarea creada por IA',
                        'prioridad' => 'media',
                    ],
                ]],
                'tokens' => 42,
            ];
        }

        if (str_contains($lower, 'buscar expediente') || str_contains($lower, 'busca expediente')) {
            return [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'fake_buscar_exp',
                    'name' => 'buscar_expedientes',
                    'arguments' => ['q' => 'EXP'],
                ]],
                'tokens' => 42,
            ];
        }

        return [
            'content' => 'Respuesta simulada sin herramientas (FakeAiService).',
            'tool_calls' => [],
            'tokens' => 42,
        ];
    }
}
