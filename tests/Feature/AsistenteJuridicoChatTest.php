<?php

declare(strict_types=1);

use App\Actions\ConsultarIaChat;
use App\Enums\TipoConsultaIa;
use App\Models\AiConsulta;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\LegalChunk;
use Database\Seeders\LegalCorpusSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('responde el chat jurídico con fuentes del corpus demo', function (): void {
    $this->seed(LegalCorpusSeeder::class);

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    Filament::setTenant($despacho);

    RateLimiter::clear('ia:user:'.$user->getAuthIdentifier());
    RateLimiter::clear('ia:despacho:'.$despacho->getKey());

    expect(LegalChunk::query()->count())->toBeGreaterThan(0);

    $resultado = app(ConsultarIaChat::class)->handle(
        user: $user,
        pregunta: '¿Qué debo revisar en una pensión alimentaria y la cuota mensual?',
        especialidad: 'familia',
    );

    expect($resultado['fuentes'])->not->toBeEmpty()
        ->and($resultado['consulta'])
        ->tipo->toBe(TipoConsultaIa::Chat)
        ->especialidad->toBe('familia')
        ->respuesta->toContain('FakeAiService');

    $raw = AiConsulta::query()->findOrFail($resultado['consulta']->getKey());
    expect($raw->fuentes)->toBeArray()->not->toBeEmpty();
});

it('puede incluir contexto de un expediente en el chat', function (): void {
    $this->seed(LegalCorpusSeeder::class);

    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);
    Filament::setTenant($despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'responsable_id' => $user->id,
        'titulo' => 'Traspaso con hipoteca demo',
    ]);

    RateLimiter::clear('ia:user:'.$user->getAuthIdentifier());
    RateLimiter::clear('ia:despacho:'.$despacho->getKey());

    $resultado = app(ConsultarIaChat::class)->handle(
        user: $user,
        pregunta: 'Resumen breve del traspaso e hipoteca para este caso.',
        expediente: $expediente,
        especialidad: 'notarial',
    );

    expect($resultado['consulta']->expediente_id)->toBe($expediente->id)
        ->and($resultado['consulta']->respuesta)->toContain('FakeAiService');
});
