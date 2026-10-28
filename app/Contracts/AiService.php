<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\BorradorIaData;
use App\Models\Expediente;

interface AiService
{
    public function resumirExpediente(Expediente $expediente): string;

    public function borradorEscrito(Expediente $expediente, BorradorIaData $data): string;

    /**
     * Completar un chat libre (asistente jurídico / RAG).
     */
    public function completarChat(string $systemPrompt, string $userMessage): string;

    /**
     * Chat con function-calling (herramientas del despacho).
     *
     * @param  list<array{role: string, content?: string|null, tool_calls?: mixed, tool_call_id?: string, name?: string}>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array{content: string|null, tool_calls: list<array{id: string, name: string, arguments: array<string, mixed>}> , tokens: int|null}
     */
    public function completarChatConHerramientas(array $messages, array $tools): array;

    public function ultimoTokens(): ?int;
}
