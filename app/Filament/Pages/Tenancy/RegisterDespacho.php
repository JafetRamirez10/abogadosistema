<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Models\Despacho;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RegisterDespacho extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Registrar despacho';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->label('Nombre del despacho')
                    ->required()
                    ->maxLength(255),
                TextInput::make('cedula_juridica')
                    ->label('Cédula jurídica')
                    ->maxLength(50),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->maxLength(255),
                Textarea::make('direccion')
                    ->label('Dirección')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data): Despacho {
            /** @var Despacho $despacho */
            $despacho = Despacho::query()->create($data);

            $user = auth()->user();
            abort_unless($user instanceof User, 403);

            $despacho->users()->attach($user);

            RolesAndPermissionsSeeder::seedRolesForDespacho($despacho);

            setPermissionsTeamId($despacho->getKey());
            $user->assignRole('admin');

            return $despacho;
        });
    }
}
