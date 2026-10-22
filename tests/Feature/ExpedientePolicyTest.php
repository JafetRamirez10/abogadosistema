<?php

declare(strict_types=1);

use App\Models\Despacho;
use App\Models\Expediente;
use App\Models\User;
use App\Policies\ExpedientePolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeExpedienteFor(User $responsable, Despacho $despacho): Expediente
{
    return Expediente::factory()->create([
        'despacho_id' => $despacho->id,
        'responsable_id' => $responsable->id,
    ]);
}

it('matriz de policies de expediente: admin / abogado / asistente', function (): void {
    $despacho = Despacho::factory()->create();
    RolesAndPermissionsSeeder::seedRolesForDespacho($despacho);
    setPermissionsTeamId($despacho->id);

    $admin = User::factory()->create();
    $abogadoResponsable = User::factory()->create();
    $abogadoOtro = User::factory()->create();
    $asistente = User::factory()->create();

    foreach ([$admin, $abogadoResponsable, $abogadoOtro, $asistente] as $user) {
        $user->despachos()->attach($despacho);
    }

    $admin->assignRole('admin');
    $abogadoResponsable->assignRole('abogado');
    $abogadoOtro->assignRole('abogado');
    $asistente->assignRole('asistente');

    $expediente = makeExpedienteFor($abogadoResponsable, $despacho);
    $policy = new ExpedientePolicy;

    // Admin: ve y edita todo
    expect($policy->view($admin, $expediente))->toBeTrue()
        ->and($policy->update($admin, $expediente))->toBeTrue();

    // Abogado responsable: ve y edita
    expect($policy->view($abogadoResponsable, $expediente))->toBeTrue()
        ->and($policy->update($abogadoResponsable, $expediente))->toBeTrue();

    // Abogado no responsable: ve, no edita
    expect($policy->view($abogadoOtro, $expediente))->toBeTrue()
        ->and($policy->update($abogadoOtro, $expediente))->toBeFalse();

    // Asistente: ve, no edita
    expect($policy->view($asistente, $expediente))->toBeTrue()
        ->and($policy->update($asistente, $expediente))->toBeFalse();
});
