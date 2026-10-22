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

    /** @var list<string> */
    public const SHIELD_RESOURCES = [
        'tarea',
        'cliente',
        'expediente',
        'documento',
        'documento::version',
        'plantilla',
        'evento',
        'actuacion',
        'hora',
        'pago',
        'parte',
        'user',
        'role',
        'despacho',
    ];

    /** @var list<string> */
    public const SHIELD_ACTIONS = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'delete_any',
        'force_delete',
        'force_delete_any',
        'restore',
        'restore_any',
        'replicate',
        'reorder',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::allPermissionNames() as $permission) {
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

        foreach (self::allPermissionNames() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $all = self::allPermissionNames();

        $abogado = array_values(array_unique([
            ...self::CUSTOM_PERMISSIONS,
            ...self::resourcePermissions([
                'tarea',
                'cliente',
                'expediente',
                'documento',
                'documento::version',
                'plantilla',
                'evento',
                'actuacion',
                'hora',
                'pago',
                'parte',
            ]),
            'usar_ia',
            'ver_montos',
            'gestionar_plantillas',
            'registrar_horas',
            'registrar_pagos',
        ]));

        $asistente = array_values(array_unique([
            ...self::resourcePermissions([
                'cliente',
                'expediente',
                'documento',
                'documento::version',
                'plantilla',
            ], ['view_any', 'view']),
            ...self::resourcePermissions([
                'tarea',
                'evento',
                'actuacion',
                'parte',
            ], ['view_any', 'view', 'create', 'update']),
        ]));

        /** @var array<string, list<string>> $rolePermissions */
        $rolePermissions = [
            'admin' => $all,
            'abogado' => $abogado,
            'asistente' => $asistente,
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
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

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
    {
        return array_values(array_unique([
            ...self::CUSTOM_PERMISSIONS,
            ...self::resourcePermissions(self::SHIELD_RESOURCES),
        ]));
    }

    /**
     * @param  list<string>  $resources
     * @param  list<string>|null  $actions
     * @return list<string>
     */
    public static function resourcePermissions(array $resources, ?array $actions = null): array
    {
        $actions ??= self::SHIELD_ACTIONS;
        $names = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $names[] = "{$action}_{$resource}";
            }
        }

        return $names;
    }
}
