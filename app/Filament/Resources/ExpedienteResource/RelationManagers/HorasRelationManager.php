<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Actions\RegistrarHora;
use App\Enums\Moneda;
use App\Models\Expediente;
use App\Models\Hora;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HorasRelationManager extends RelationManager
{
    protected static string $relationship = 'horas';

    protected static ?string $title = 'Horas';

    protected static ?string $modelLabel = 'hora';

    protected static ?string $pluralModelLabel = 'horas';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('ver_montos') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha')
                            ->required()
                            ->native(false)
                            ->default(now('America/Costa_Rica')),
                        Forms\Components\TextInput::make('minutos')
                            ->label('Minutos')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1440),
                        Forms\Components\TextInput::make('descripcion')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('tarifa')
                            ->label('Tarifa')
                            ->numeric()
                            ->default(fn (): mixed => auth()->user()?->tarifa_hora),
                        Forms\Components\Select::make('moneda')
                            ->label('Moneda')
                            ->options(Moneda::class)
                            ->default(fn (): mixed => auth()->user()->moneda_tarifa ?? Moneda::CRC)
                            ->required(),
                        Forms\Components\Toggle::make('facturable')
                            ->label('Facturable')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('minutos')
                    ->label('Minutos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('tarifa')
                    ->label('Tarifa')
                    ->formatStateUsing(fn ($state, Hora $record): string => $state === null
                        ? '—'
                        : formatear_monto($state, $record->moneda)),
                Tables\Columns\IconColumn::make('facturable')
                    ->label('Facturable')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registró')
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data): Hora {
                        /** @var Expediente $owner */
                        $owner = $this->getOwnerRecord();

                        return app(RegistrarHora::class)->handle([
                            ...$data,
                            'despacho_id' => $owner->despacho_id,
                            'expediente_id' => $owner->getKey(),
                            'user_id' => auth()->id(),
                        ]);
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
            ->emptyStateHeading('Sin horas registradas')
            ->emptyStateDescription('Registrá el tiempo dedicado a este expediente.');
    }
}
