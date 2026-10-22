<?php

declare(strict_types=1);

use App\Enums\EstadoEvento;
use App\Jobs\EnviarAlertasPlazos;
use App\Mail\PlazoProximoMail;
use App\Models\Despacho;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('envía alerta de plazo solo una vez', function (): void {
    Mail::fake();

    $despacho = Despacho::factory()->create();
    $responsable = User::factory()->create(['email' => 'abogado@example.com']);
    $responsable->despachos()->attach($despacho);

    $evento = Evento::factory()->dentroDeAviso(2, 3)->create([
        'despacho_id' => $despacho->id,
        'responsable_id' => $responsable->id,
        'titulo' => 'Audiencia preliminar',
    ]);

    (new EnviarAlertasPlazos)->handle();
    (new EnviarAlertasPlazos)->handle();

    Mail::assertSent(PlazoProximoMail::class, 1);

    expect($evento->fresh()->alerta_enviada_at)->not->toBeNull();
});

it('el mail de plazo no incluye cédulas ni montos', function (): void {
    Mail::fake();

    $despacho = Despacho::factory()->create();
    $responsable = User::factory()->create(['email' => 'abogado@example.com']);
    $responsable->despachos()->attach($despacho);

    $expediente = Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'responsable_id' => $responsable->id,
        'cuantia' => 1_500_000,
        'honorario_pactado' => 250_000,
        'numero_interno' => 'EXP-9001',
    ]);

    $evento = Evento::factory()->dentroDeAviso(1, 3)->create([
        'despacho_id' => $despacho->id,
        'expediente_id' => $expediente->id,
        'responsable_id' => $responsable->id,
        'titulo' => 'Vencimiento de plazo',
        'notas' => 'Preparar memorial',
    ]);

    (new EnviarAlertasPlazos)->handle();

    Mail::assertSent(PlazoProximoMail::class, function (PlazoProximoMail $mail) use ($evento): bool {
        $html = $mail->render();

        expect($html)->toContain('Vencimiento de plazo')
            ->and($html)->toContain('EXP-9001')
            ->and($html)->not->toContain('1_500_000')
            ->and($html)->not->toContain('1500000')
            ->and($html)->not->toContain('250000')
            ->and($html)->not->toContain('honorario')
            ->and($html)->not->toContain('cuantía')
            ->and($html)->not->toContain('cédula')
            ->and($html)->not->toContain('cedula');

        return $mail->evento->is($evento);
    });
});

it('el job de alertas respeta el tenant por despacho', function (): void {
    Mail::fake();

    $despachoA = Despacho::factory()->create();
    $despachoB = Despacho::factory()->create();

    $responsableA = User::factory()->create(['email' => 'a@example.com']);
    $responsableB = User::factory()->create(['email' => 'b@example.com']);
    $responsableA->despachos()->attach($despachoA);
    $responsableB->despachos()->attach($despachoB);

    $eventoA = Evento::factory()->dentroDeAviso(1, 3)->create([
        'despacho_id' => $despachoA->id,
        'responsable_id' => $responsableA->id,
        'titulo' => 'Evento A',
    ]);

    $eventoB = Evento::factory()->dentroDeAviso(1, 3)->create([
        'despacho_id' => $despachoB->id,
        'responsable_id' => $responsableB->id,
        'titulo' => 'Evento B',
    ]);

    $eventoLejos = Evento::factory()->create([
        'despacho_id' => $despachoA->id,
        'responsable_id' => $responsableA->id,
        'inicio' => now('America/Costa_Rica')->addDays(10),
        'dias_aviso' => 3,
        'estado' => EstadoEvento::Pendiente,
        'alerta_enviada_at' => null,
        'titulo' => 'Evento lejos',
    ]);

    (new EnviarAlertasPlazos)->handle();

    Mail::assertSent(PlazoProximoMail::class, 2);
    Mail::assertSent(PlazoProximoMail::class, fn (PlazoProximoMail $mail): bool => $mail->evento->is($eventoA));
    Mail::assertSent(PlazoProximoMail::class, fn (PlazoProximoMail $mail): bool => $mail->evento->is($eventoB));

    expect($eventoA->fresh()->alerta_enviada_at)->not->toBeNull()
        ->and($eventoB->fresh()->alerta_enviada_at)->not->toBeNull()
        ->and($eventoLejos->fresh()->alerta_enviada_at)->toBeNull();
});
