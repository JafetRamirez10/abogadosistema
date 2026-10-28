<?php

declare(strict_types=1);

use App\Models\LegalChunk;
use App\Models\LegalSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['services.corpus_api.token' => 'test-corpus-token-secret']);
});

it('rechaza requests sin token', function (): void {
    $this->getJson('/api/v1/corpus/health')->assertUnauthorized();
});

it('responde health con bearer token', function (): void {
    $this->withToken('test-corpus-token-secret')
        ->getJson('/api/v1/corpus/health')
        ->assertOk()
        ->assertJsonPath('ok', true);
});

it('hace upsert bulk de fuente y chunks', function (): void {
    $payload = [
        'replace_chunks' => true,
        'source' => [
            'codigo' => 'cr-test-ley',
            'titulo' => 'Ley de prueba',
            'tipo' => 'ley',
            'materia' => 'civil',
            'origen' => 'https://example.test/ley',
            'es_demo' => false,
        ],
        'chunks' => [
            [
                'external_id' => 'art-1',
                'referencia' => 'Art. 1',
                'titulo' => 'Objeto',
                'contenido' => 'Esta es una norma de prueba verificada para el corpus.',
                'keywords' => ['objeto', 'ley'],
            ],
            [
                'external_id' => 'art-2',
                'referencia' => 'Art. 2',
                'contenido' => 'Segundo artículo de prueba sobre contratos.',
                'keywords' => ['contratos'],
            ],
        ],
    ];

    $this->withToken('test-corpus-token-secret')
        ->postJson('/api/v1/corpus/bulk', $payload)
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('codigo', 'cr-test-ley')
        ->assertJsonPath('chunks_upserted', 2);

    expect(LegalSource::query()->where('codigo', 'cr-test-ley')->exists())->toBeTrue()
        ->and(LegalChunk::query()->count())->toBe(2);

    $this->withToken('test-corpus-token-secret')
        ->getJson('/api/v1/corpus/search?q=contratos')
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('count', 1);
});

it('actualiza chunk por external_id sin duplicar', function (): void {
    $source = LegalSource::query()->create([
        'codigo' => 'cr-upd',
        'titulo' => 'Fuente',
        'tipo' => 'doctrina',
        'es_demo' => false,
    ]);

    $this->withToken('test-corpus-token-secret')
        ->putJson('/api/v1/corpus/sources/cr-upd/chunks', [
            'external_id' => 'p-1',
            'contenido' => 'Versión 1',
            'referencia' => '§1',
        ])
        ->assertCreated();

    $this->withToken('test-corpus-token-secret')
        ->putJson('/api/v1/corpus/sources/cr-upd/chunks', [
            'external_id' => 'p-1',
            'contenido' => 'Versión 2 actualizada',
            'referencia' => '§1',
        ])
        ->assertOk();

    expect(LegalChunk::query()->where('legal_source_id', $source->id)->count())->toBe(1)
        ->and(LegalChunk::query()->where('external_id', 'p-1')->value('contenido'))->toBe('Versión 2 actualizada');
});
