<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\LegalChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Recuperación léxica simple (MVP). Más adelante se puede sustituir por embeddings.
 */
final class LegalCorpusRetriever
{
    /**
     * @return list<array{id: int, score: float, referencia: string|null, titulo: string|null, contenido: string, fuente: string, materia: string|null}>
     */
    public function retrieve(string $query, ?string $materia = null, int $limit = 5): array
    {
        $tokens = $this->tokenize($query);
        if ($tokens === []) {
            return [];
        }

        $chunks = LegalChunk::query()
            ->with('source')
            ->when(
                filled($materia) && $materia !== 'general',
                fn ($q) => $q->where(function ($inner) use ($materia): void {
                    $inner->where('materia', $materia)->orWhereNull('materia');
                }),
            )
            ->get();

        /** @var Collection<int, array{id: int, score: float, referencia: string|null, titulo: string|null, contenido: string, fuente: string, materia: string|null}> $scored */
        $scored = $chunks->map(function (LegalChunk $chunk) use ($tokens): array {
            $haystack = Str::lower(
                ($chunk->titulo ?? '').' '.($chunk->referencia ?? '').' '.$chunk->contenido
                .' '.implode(' ', $chunk->keywords ?? []),
            );

            $score = 0.0;
            foreach ($tokens as $token) {
                if (str_contains($haystack, $token)) {
                    $score += 1.0;
                    $score += min(3.0, substr_count($haystack, $token) * 0.25);
                }
            }

            foreach ($chunk->keywords ?? [] as $kw) {
                $kw = Str::lower((string) $kw);
                if ($kw !== '' && in_array($kw, $tokens, true)) {
                    $score += 2.0;
                }
            }

            return [
                'id' => (int) $chunk->getKey(),
                'score' => $score,
                'referencia' => $chunk->referencia,
                'titulo' => $chunk->titulo,
                'contenido' => $chunk->contenido,
                'fuente' => $chunk->source?->titulo ?? 'Fuente',
                'materia' => $chunk->materia,
            ];
        })
            ->filter(fn (array $row): bool => $row['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return $scored->all();
    }

    /**
     * @param  list<array{id: int, score: float, referencia: string|null, titulo: string|null, contenido: string, fuente: string, materia: string|null}>  $chunks
     */
    public function formatForPrompt(array $chunks): string
    {
        if ($chunks === []) {
            return '(No se recuperaron fragmentos del corpus para esta consulta.)';
        }

        $parts = [];
        foreach ($chunks as $i => $chunk) {
            $n = $i + 1;
            $ref = $chunk['referencia'] ?: ($chunk['titulo'] ?: 'sin ref.');
            $parts[] = "[Fuente {$n}] {$chunk['fuente']} — {$ref}\n{$chunk['contenido']}";
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $query): array
    {
        $normalized = Str::lower(Str::ascii($query));
        $parts = preg_split('/[^a-z0-9áéíóúñü]+/u', $normalized) ?: [];

        $stop = [
            'de', 'la', 'el', 'los', 'las', 'un', 'una', 'y', 'o', 'en', 'del', 'al', 'que', 'por',
            'para', 'con', 'sobre', 'como', 'cual', 'qué', 'es', 'se', 'su', 'sus', 'me', 'mi',
            'the', 'a', 'an',
        ];

        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_strlen($part) < 3 || in_array($part, $stop, true)) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }
}
