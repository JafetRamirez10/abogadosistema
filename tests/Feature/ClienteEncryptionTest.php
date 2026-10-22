<?php

declare(strict_types=1);

use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Models\Despacho;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('almacena la cédula encriptada en la base de datos', function (): void {
    $despacho = Despacho::factory()->create();
    $cedulaPlana = '123456789';

    $cliente = Cliente::factory()->create([
        'despacho_id' => $despacho->id,
        'tipo' => TipoCliente::Fisica,
        'cedula' => $cedulaPlana,
    ]);

    $raw = DB::table('clientes')->where('id', $cliente->id)->value('cedula');

    expect($raw)->not->toBe($cedulaPlana)
        ->and($raw)->not->toContain('123456789')
        ->and($cliente->fresh()->cedula)->toBe($cedulaPlana);
});
