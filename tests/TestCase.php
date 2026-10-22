<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Despacho;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Autentica un usuario con rol Spatie en el despacho (tenant Filament).
     *
     * @param  'admin'|'abogado'|'asistente'  $role
     */
    protected function actingAsTenantUser(string $role, ?Despacho $despacho = null): User
    {
        $despacho ??= Despacho::factory()->create();

        RolesAndPermissionsSeeder::seedRolesForDespacho($despacho);

        $user = User::factory()->create();
        $user->despachos()->attach($despacho);

        setPermissionsTeamId($despacho->getKey());
        $user->assignRole($role);

        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($despacho);

        return $user->fresh() ?? $user;
    }
}
