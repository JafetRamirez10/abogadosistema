<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditDespachoProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Perfil del despacho';
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
}
