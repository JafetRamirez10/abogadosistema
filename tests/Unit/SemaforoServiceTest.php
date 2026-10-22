<?php

declare(strict_types=1);

use App\Enums\EstadoEvento;
use App\Enums\Semaforo;
use App\Services\SemaforoService;
use Carbon\Carbon;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-21 12:00:00', 'America/Costa_Rica'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('calcula semáforo para 0, 1, 2, 5, 6 y −1 días', function (int $dias, Semaforo $esperado): void {
    $fecha = now('America/Costa_Rica')->startOfDay()->addDays($dias);

    expect(SemaforoService::para($fecha, EstadoEvento::Pendiente))->toBe($esperado)
        ->and(SemaforoService::diasHasta($fecha))->toBe($dias);
})->with([
    'hoy (0)' => [0, Semaforo::Rojo],
    '1 día' => [1, Semaforo::Rojo],
    '2 días' => [2, Semaforo::Amarillo],
    '5 días' => [5, Semaforo::Amarillo],
    '6 días' => [6, Semaforo::Verde],
    'vencido (−1)' => [-1, Semaforo::Vencido],
]);

it('devuelve verde cuando el evento no está pendiente', function (): void {
    $fecha = now('America/Costa_Rica')->addDay();

    expect(SemaforoService::para($fecha, EstadoEvento::Cumplido))->toBe(Semaforo::Verde)
        ->and(SemaforoService::para($fecha, EstadoEvento::Cancelada))->toBe(Semaforo::Verde);
});

it('formatea badge con días', function (): void {
    expect(Semaforo::Rojo->badge(1))->toBe('Rojo · 1 día')
        ->and(Semaforo::Amarillo->badge(2))->toBe('Amarillo · 2 días')
        ->and(Semaforo::Rojo->badge(0))->toBe('Rojo · hoy')
        ->and(Semaforo::Vencido->badge(-1))->toBe('Vencido · 1 día');
});
