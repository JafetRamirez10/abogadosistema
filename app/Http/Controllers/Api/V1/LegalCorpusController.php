<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LegalChunk;
use App\Models\LegalSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class LegalCorpusController extends Controller
{
    /** @var list<string> */
    private const TIPOS = ['ley', 'jurisprudencia', 'doctrina', 'articulo', 'codigo', 'otro'];

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => 'corpus-legal',
            'sources' => LegalSource::query()->count(),
            'chunks' => LegalChunk::query()->count(),
        ]);
    }

    public function indexSources(Request $request): JsonResponse
    {
        $q = LegalSource::query()->withCount('chunks')->orderBy('codigo');

        if ($request->filled('tipo')) {
            $q->where('tipo', $request->string('tipo'));
        }
        if ($request->filled('materia')) {
            $q->where('materia', $request->string('materia'));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $q->where(function ($inner) use ($term): void {
                $inner->where('titulo', 'like', $term)
                    ->orWhere('codigo', 'like', $term)
                    ->orWhere('external_id', 'like', $term);
            });
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 25)));

        return response()->json($q->paginate($perPage));
    }

    public function upsertSource(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:64'],
            'titulo' => ['required', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', Rule::in(self::TIPOS)],
            'materia' => ['nullable', 'string', 'max:64'],
            'origen' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:128'],
            'vigente_desde' => ['nullable', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],
            'es_demo' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $source = LegalSource::query()->updateOrCreate(
            ['codigo' => $data['codigo']],
            [
                'titulo' => $data['titulo'],
                'tipo' => $data['tipo'] ?? 'ley',
                'materia' => $data['materia'] ?? null,
                'origen' => $data['origen'] ?? null,
                'external_id' => $data['external_id'] ?? null,
                'vigente_desde' => $data['vigente_desde'] ?? null,
                'vigente_hasta' => $data['vigente_hasta'] ?? null,
                'es_demo' => (bool) ($data['es_demo'] ?? false),
                'metadata' => $data['metadata'] ?? null,
                'indexado_at' => now(),
            ],
        );

        return response()->json([
            'ok' => true,
            'source' => $source->fresh()->loadCount('chunks'),
        ], $source->wasRecentlyCreated ? 201 : 200);
    }

    public function showSource(string $codigo): JsonResponse
    {
        $source = LegalSource::query()
            ->where('codigo', $codigo)
            ->with(['chunks' => fn ($q) => $q->orderBy('orden')->orderBy('id')])
            ->firstOrFail();

        return response()->json(['source' => $source]);
    }

    public function upsertChunk(Request $request, string $codigo): JsonResponse
    {
        $source = LegalSource::query()->where('codigo', $codigo)->firstOrFail();

        $data = $request->validate([
            'referencia' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:128'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'contenido' => ['required', 'string', 'max:50000'],
            'materia' => ['nullable', 'string', 'max:64'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:80'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);

        $externalId = $data['external_id']
            ?? ($data['referencia'] ? 'ref:'.$data['referencia'] : null);

        if ($externalId === null) {
            $chunk = $source->chunks()->create([
                'referencia' => $data['referencia'] ?? null,
                'titulo' => $data['titulo'] ?? null,
                'contenido' => $data['contenido'],
                'materia' => $data['materia'] ?? $source->materia,
                'keywords' => $data['keywords'] ?? null,
                'orden' => $data['orden'] ?? 0,
                'metadata' => $data['metadata'] ?? null,
            ]);
            $created = true;
        } else {
            $chunk = LegalChunk::query()->updateOrCreate(
                [
                    'legal_source_id' => $source->getKey(),
                    'external_id' => $externalId,
                ],
                [
                    'referencia' => $data['referencia'] ?? null,
                    'titulo' => $data['titulo'] ?? null,
                    'contenido' => $data['contenido'],
                    'materia' => $data['materia'] ?? $source->materia,
                    'keywords' => $data['keywords'] ?? null,
                    'orden' => $data['orden'] ?? 0,
                    'metadata' => $data['metadata'] ?? null,
                ],
            );
            $created = $chunk->wasRecentlyCreated;
        }

        $source->forceFill(['indexado_at' => now()])->save();

        return response()->json([
            'ok' => true,
            'chunk' => $chunk,
        ], $created ? 201 : 200);
    }

    public function bulkUpsert(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'source' => ['required', 'array'],
            'source.codigo' => ['required', 'string', 'max:64'],
            'source.titulo' => ['required', 'string', 'max:255'],
            'source.tipo' => ['nullable', 'string', Rule::in(self::TIPOS)],
            'source.materia' => ['nullable', 'string', 'max:64'],
            'source.origen' => ['nullable', 'string', 'max:255'],
            'source.external_id' => ['nullable', 'string', 'max:128'],
            'source.vigente_desde' => ['nullable', 'date'],
            'source.vigente_hasta' => ['nullable', 'date'],
            'source.es_demo' => ['nullable', 'boolean'],
            'source.metadata' => ['nullable', 'array'],
            'chunks' => ['required', 'array', 'min:1', 'max:200'],
            'chunks.*.referencia' => ['nullable', 'string', 'max:255'],
            'chunks.*.external_id' => ['nullable', 'string', 'max:128'],
            'chunks.*.titulo' => ['nullable', 'string', 'max:255'],
            'chunks.*.contenido' => ['required', 'string', 'max:50000'],
            'chunks.*.materia' => ['nullable', 'string', 'max:64'],
            'chunks.*.keywords' => ['nullable', 'array'],
            'chunks.*.orden' => ['nullable', 'integer', 'min:0'],
            'chunks.*.metadata' => ['nullable', 'array'],
            'replace_chunks' => ['nullable', 'boolean'],
        ]);

        $result = DB::transaction(function () use ($payload): array {
            $s = $payload['source'];
            $source = LegalSource::query()->updateOrCreate(
                ['codigo' => $s['codigo']],
                [
                    'titulo' => $s['titulo'],
                    'tipo' => $s['tipo'] ?? 'ley',
                    'materia' => $s['materia'] ?? null,
                    'origen' => $s['origen'] ?? null,
                    'external_id' => $s['external_id'] ?? null,
                    'vigente_desde' => $s['vigente_desde'] ?? null,
                    'vigente_hasta' => $s['vigente_hasta'] ?? null,
                    'es_demo' => (bool) ($s['es_demo'] ?? false),
                    'metadata' => $s['metadata'] ?? null,
                    'indexado_at' => now(),
                ],
            );

            if (! empty($payload['replace_chunks'])) {
                $source->chunks()->delete();
            }

            $upserted = 0;
            foreach ($payload['chunks'] as $i => $chunkData) {
                $externalId = $chunkData['external_id']
                    ?? (isset($chunkData['referencia']) ? 'ref:'.$chunkData['referencia'] : 'auto:'.$i);

                LegalChunk::query()->updateOrCreate(
                    [
                        'legal_source_id' => $source->getKey(),
                        'external_id' => $externalId,
                    ],
                    [
                        'referencia' => $chunkData['referencia'] ?? null,
                        'titulo' => $chunkData['titulo'] ?? null,
                        'contenido' => $chunkData['contenido'],
                        'materia' => $chunkData['materia'] ?? $source->materia,
                        'keywords' => $chunkData['keywords'] ?? null,
                        'orden' => $chunkData['orden'] ?? $i,
                        'metadata' => $chunkData['metadata'] ?? null,
                    ],
                );
                $upserted++;
            }

            return [
                'source_id' => $source->getKey(),
                'codigo' => $source->codigo,
                'chunks_upserted' => $upserted,
                'chunks_total' => $source->chunks()->count(),
            ];
        });

        return response()->json(['ok' => true, ...$result]);
    }

    public function destroySource(string $codigo): JsonResponse
    {
        $source = LegalSource::query()->where('codigo', $codigo)->firstOrFail();
        $source->delete();

        return response()->json(['ok' => true, 'deleted' => $codigo]);
    }

    public function searchChunks(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:500'],
            'materia' => ['nullable', 'string', 'max:64'],
            'tipo' => ['nullable', 'string', Rule::in(self::TIPOS)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $data['limit'] ?? 10;
        $term = '%'.$data['q'].'%';

        $chunks = LegalChunk::query()
            ->with('source:id,codigo,titulo,tipo,materia,origen')
            ->when(isset($data['materia']), fn ($q) => $q->where('materia', $data['materia']))
            ->when(isset($data['tipo']), fn ($q) => $q->whereHas('source', fn ($s) => $s->where('tipo', $data['tipo'])))
            ->where(function ($q) use ($term): void {
                $q->where('contenido', 'like', $term)
                    ->orWhere('titulo', 'like', $term)
                    ->orWhere('referencia', 'like', $term);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'count' => $chunks->count(),
            'chunks' => $chunks,
        ]);
    }
}
