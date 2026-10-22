<?php

declare(strict_types=1);

use App\Actions\ConsultarIa;
use App\Contracts\AiService;
use App\DTOs\BorradorIaData;
use App\Enums\Materia;
use App\Enums\RolParte;
use App\Enums\TipoConsultaIa;
use App\Enums\TipoEscritoIa;
use App\Models\Actuacion;
use App\Models\AiConsulta;
use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\Parte;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\FakeAiService;
use App\Services\Ai\OpenAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

uses(RefreshDatabase::class);

it('resuelve FakeAiService en testing y genera resumen', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
        'materia' => Materia::Civil,
    ]);

    $ai = app(AiService::class);
    expect($ai)->toBeInstanceOf(FakeAiService::class);

    $texto = $ai->resumirExpediente($expediente);

    expect($texto)->toContain('FakeAiService')
        ->and($texto)->toContain($expediente->numero_interno);
});

it('el contexto no incluye datos personales ni montos', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
        'materia' => Materia::Laboral,
        'juzgado' => 'Juzgado de Trabajo I Circuito',
        'cuantia' => 1_500_000.00,
        'honorario_pactado' => 250_000.00,
    ]);

    Parte::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'expediente_id' => $expediente->getKey(),
        'nombre' => 'Ana Pérez',
        'cedula' => '109990888',
        'rol' => RolParte::Actor,
        'contacto' => '8888-7777',
    ]);

    Actuacion::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'expediente_id' => $expediente->getKey(),
        'user_id' => $user->getKey(),
        'descripcion' => 'Se notificó a juan@correo.com al 2222-3333 por ¢1.250.000,00',
    ]);

    $contexto = app(AiContextBuilder::class)->build($expediente->fresh(['partes', 'actuaciones']));

    expect($contexto)->toContain('Laboral')
        ->and($contexto)->toContain('Juzgado de Trabajo I Circuito')
        ->and($contexto)->toContain('Actor: Ana Pérez')
        ->and($contexto)->not->toContain('109990888')
        ->and($contexto)->not->toContain('8888-7777')
        ->and($contexto)->not->toContain('juan@correo.com')
        ->and($contexto)->not->toContain('1,500,000')
        ->and($contexto)->not->toContain('1500000')
        ->and($contexto)->not->toContain('250000')
        ->and($contexto)->not->toContain('honorario');
});

it('responde 429 al superar el rate limit de IA', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    RateLimiter::clear('ia:user:'.$user->getAuthIdentifier());
    RateLimiter::clear('ia:despacho:'.$despacho->getKey());

    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('ia.resumir', $expediente))
            ->assertSuccessful();
    }

    $this->postJson(route('ia.resumir', $expediente))
        ->assertStatus(429);
});

it('guarda la respuesta de IA encriptada en la base de datos', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $consulta = app(ConsultarIa::class)->resumir($expediente, $user);

    expect($consulta->tipo)->toBe(TipoConsultaIa::Resumen)
        ->and($consulta->respuesta)->toContain('FakeAiService');

    $raw = DB::table('ai_consultas')->where('id', $consulta->getKey())->value('respuesta');

    expect($raw)->not->toBe($consulta->respuesta)
        ->and($raw)->not->toContain('FakeAiService')
        ->and(AiConsulta::query()->findOrFail($consulta->getKey())->respuesta)->toContain('FakeAiService');
});

it('no registra la API key de OpenAI en los logs', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $secretKey = 'sk-test-SECRET-KEY-NO-DEBE-APARECER-EN-LOGS';
    config(['services.openai.key' => $secretKey, 'services.openai.model' => 'gpt-4o-mini']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Resumen de prueba']],
            ],
            'usage' => ['total_tokens' => 42],
        ], 200),
    ]);

    $service = app(OpenAiService::class);
    expect($service->resumirExpediente($expediente))->toBe('Resumen de prueba')
        ->and($service->ultimoTokens())->toBe(42);

    Http::assertSent(function ($request) use ($secretKey): bool {
        // La key viaja en el header Authorization (esperado); no debe ir en el body.
        $body = $request->body();

        return $request->hasHeader('Authorization', 'Bearer '.$secretKey)
            && ! str_contains($body, $secretKey);
    });

    Http::fake([
        'api.openai.com/*' => Http::response(['error' => 'boom'], 500),
    ]);

    try {
        $service->resumirExpediente($expediente);
        expect(false)->toBeTrue('Debía lanzar RuntimeException');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->not->toContain($secretKey)
            ->and($e->getTraceAsString())->not->toContain($secretKey);
    }

    // El servicio solo registra status/mensaje genérico, nunca la key.
    $fuente = (string) file_get_contents(app_path('Services/Ai/OpenAiService.php'));
    expect($fuente)->not->toMatch('/Log::\w+\([^;]*\$key/')
        ->and($fuente)->toContain("Log::warning('OpenAI:");
});

it('genera borrador con FakeAiService vía ConsultarIa', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $consulta = app(ConsultarIa::class)->borrador(
        $expediente,
        $user,
        new BorradorIaData(
            instrucciones: 'Redactar contestación breve sin admitir hechos.',
            tipoEscrito: TipoEscritoIa::Contestacion,
        ),
    );

    expect($consulta->tipo)->toBe(TipoConsultaIa::Borrador)
        ->and($consulta->respuesta)->toContain('Contestación')
        ->and($consulta->respuesta)->toContain('contestación breve');
});

it('lanza TooManyRequestsHttpException desde ConsultarIa al exceder límite', function (): void {
    $despacho = Despacho::factory()->create();
    $user = $this->actingAsTenantUser('abogado', $despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->getKey(),
        'responsable_id' => $user->getKey(),
    ]);

    $key = 'ia:user:'.$user->getAuthIdentifier();
    RateLimiter::clear($key);
    RateLimiter::clear('ia:despacho:'.$despacho->getKey());

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($key, 60);
    }

    expect(fn () => app(ConsultarIa::class)->resumir($expediente, $user))
        ->toThrow(TooManyRequestsHttpException::class);
});
