<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Despacho;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /** @var list<string> */
    public const CUSTOM_PERMISSIONS = [
        'ver_montos',
        'usar_ia',
        'gestionar_usuarios',
        'gestionar_plantillas',
        'registrar_horas',
        'registrar_pagos',
    ];

    /** @var array<string, list<string>> */
    public const ROLE_PERMISSIONS = [
        'admin' => self::CUSTOM_PERMISSIONS,
        'abogado' => [
            'ver_montos',
            'usar_ia',
            'gestionar_plantillas',
            'registrar_horas',
            'registrar_pagos',
        ],
        'asistente' => [],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::CUSTOM_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Despacho::query()->each(function (Despacho $despacho): void {
            self::seedRolesForDespacho($despacho);
        });
    }

    public static function seedRolesForDespacho(Despacho $despacho): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        setPermissionsTeamId($despacho->getKey());

        foreach (self::CUSTOM_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            /** @var Role $role */
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'despacho_id' => $despacho->getKey(),
                ],
            );

            $role->syncPermissions($permissions);
        }
    }
}
