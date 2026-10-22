<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Enums\RolParte;
use App\Models\Expediente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PartesRelationManager extends RelationManager
{
    protected static string $relationship = 'partes';

    protected static ?string $title = 'Partes';

    protected static ?string $modelLabel = 'parte';

    protected static ?string $pluralModelLabel = 'partes';

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
                        Forms\Components\Select::make('rol')
                            ->label('Rol')
                            ->options(RolParte::class)
                            ->required(),
                        Forms\Components\TextInput::make('cedula')
                            ->label('Cédula')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('abogado_contrario')
                            ->label('Abogado contrario')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contacto')
                            ->label('Contacto')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rol')
                    ->label('Rol')
                    ->badge(),
                Tables\Columns\TextColumn::make('abogado_contrario')
                    ->label('Abogado contrario')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contacto')
                    ->label('Contacto')
                    ->placeholder('—')
                    ->toggleable(),
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin partes')
            ->emptyStateDescription('Agregá las partes involucradas en este expediente.');
    }
}
