<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Enums\ConceptoCuotaAlimentaria;
use App\Enums\EstadoCuotaAlimentaria;
use App\Enums\Materia;
use App\Enums\Moneda;
use App\Models\CuotaAlimentaria;
use App\Models\Expediente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CuotasAlimentariasRelationManager extends RelationManager
{
    protected static string $relationship = 'cuotasAlimentarias';

    protected static ?string $title = 'Cuotas alimentarias';

    protected static ?string $modelLabel = 'cuota';

    protected static ?string $pluralModelLabel = 'cuotas';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Expediente
            && $ownerRecord->materia === Materia::Familia
            && $ownerRecord->pensionAlimentaria()->exists();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\Select::make('concepto')
                            ->label('Concepto')
                            ->options(ConceptoCuotaAlimentaria::class)
                            ->required(),
                        Forms\Components\TextInput::make('periodo')
                            ->label('Periodo')
                            ->placeholder('YYYY-MM')
                            ->maxLength(7),
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->prefix('₡'),
                        Forms\Components\Select::make('moneda')
                            ->label('Moneda')
                            ->options(Moneda::class)
                            ->default(Moneda::CRC)
                            ->required(),
                        Forms\Components\DatePicker::make('vence_el')
                            ->label('Vence el')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options(EstadoCuotaAlimentaria::class)
                            ->default(EstadoCuotaAlimentaria::Pendiente)
                            ->required(),
                        Forms\Components\Textarea::make('notas')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('vence_el', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('concepto')->label('Concepto')->badge(),
                Tables\Columns\TextColumn::make('periodo')->label('Periodo')->placeholder('—'),
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money(fn (CuotaAlimentaria $record): string => $record->moneda->value),
                Tables\Columns\TextColumn::make('monto_pagado')
                    ->label('Pagado')
                    ->money(fn (CuotaAlimentaria $record): string => $record->moneda->value),
                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money(fn (CuotaAlimentaria $record): string => $record->moneda->value)
                    ->color(fn (CuotaAlimentaria $record): string => $record->en_mora ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('vence_el')->label('Vence')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge(),
                Tables\Columns\IconColumn::make('en_mora')->label('Mora')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Expediente $owner */
                        $owner = $this->getOwnerRecord();
                        $pension = $owner->pensionAlimentaria;
                        $data['despacho_id'] = $owner->despacho_id;
                        $data['pension_alimentaria_id'] = $pension?->getKey();
                        $data['monto_pagado'] = $data['monto_pagado'] ?? 0;
                        $data['moneda'] = $data['moneda'] ?? $pension?->moneda?->value ?? Moneda::CRC->value;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin cuotas')
            ->emptyStateDescription('Generá la cuota del mes desde Pensión o creá una manual (aguinaldo, escolar, etc.).');
    }
}
