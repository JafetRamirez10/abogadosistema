<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Actions\RegistrarPago;
use App\Enums\MetodoPago;
use App\Enums\Moneda;
use App\Models\Expediente;
use App\Models\Pago;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Pagos';

    protected static ?string $modelLabel = 'pago';

    protected static ?string $pluralModelLabel = 'pagos';

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
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\Select::make('moneda')
                            ->label('Moneda')
                            ->options(Moneda::class)
                            ->default(fn (): Moneda => $this->getOwnerRecord()->moneda ?? Moneda::CRC)
                            ->required(),
                        Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha')
                            ->required()
                            ->native(false)
                            ->default(now('America/Costa_Rica')),
                        Forms\Components\Select::make('metodo')
                            ->label('Método')
                            ->options(MetodoPago::class)
                            ->required(),
                        Forms\Components\TextInput::make('referencia')
                            ->label('Referencia')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nota')
                            ->label('Nota')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('monto')
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->formatStateUsing(fn ($state, Pago $record): string => formatear_monto($state, $record->moneda))
                    ->sortable(),
                Tables\Columns\TextColumn::make('metodo')
                    ->label('Método')
                    ->badge(),
                Tables\Columns\TextColumn::make('referencia')
                    ->label('Referencia')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registró')
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data): Pago {
                        /** @var Expediente $owner */
                        $owner = $this->getOwnerRecord();

                        return app(RegistrarPago::class)->handle([
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
            ->emptyStateHeading('Sin pagos')
            ->emptyStateDescription('Registrá abonos y pagos del cliente.');
    }
}
