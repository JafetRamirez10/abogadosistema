<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Enums\Materia;
use App\Models\Expediente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BeneficiariosFamiliaresRelationManager extends RelationManager
{
    protected static string $relationship = 'beneficiariosFamiliares';

    protected static ?string $title = 'Beneficiarios / menores';

    protected static ?string $modelLabel = 'beneficiario';

    protected static ?string $pluralModelLabel = 'beneficiarios';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Expediente
            && $ownerRecord->materia === Materia::Familia;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('parentesco')
                            ->label('Parentesco')
                            ->placeholder('Hijo/a, nieto/a…')
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->native(false),
                        Forms\Components\Toggle::make('es_menor')
                            ->label('Es menor de edad')
                            ->default(true),
                        Forms\Components\TextInput::make('cedula')
                            ->label('Cédula')
                            ->maxLength(20),
                        Forms\Components\Textarea::make('notas')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('parentesco')->label('Parentesco')->placeholder('—'),
                Tables\Columns\IconColumn::make('es_menor')->label('Menor')->boolean(),
                Tables\Columns\TextColumn::make('fecha_nacimiento')
                    ->label('Nacimiento')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Expediente $owner */
                        $owner = $this->getOwnerRecord();
                        $data['despacho_id'] = $owner->despacho_id;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin beneficiarios')
            ->emptyStateDescription('Registrá hijos o beneficiarios del proceso de familia.');
    }
}
