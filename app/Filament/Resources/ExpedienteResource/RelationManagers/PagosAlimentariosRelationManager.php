<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Enums\Materia;
use App\Enums\MetodoPago;
use App\Enums\Moneda;
use App\Models\CuotaAlimentaria;
use App\Models\Expediente;
use App\Models\PagoAlimentario;
use App\Services\PensionAlimentariaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PagosAlimentariosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagosAlimentarios';

    protected static ?string $title = 'Pagos del obligado';

    protected static ?string $modelLabel = 'pago alimentario';

    protected static ?string $pluralModelLabel = 'pagos alimentarios';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Expediente
            && $ownerRecord->materia === Materia::Familia
            && $ownerRecord->pensionAlimentaria()->exists();
    }

    public function form(Form $form): Form
    {
        /** @var Expediente $owner */
        $owner = $this->getOwnerRecord();

        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\Select::make('cuota_alimentaria_id')
                            ->label('Aplicar a cuota')
                            ->options(
                                $owner->cuotasAlimentarias()
                                    ->orderByDesc('vence_el')
                                    ->get()
                                    ->mapWithKeys(fn (CuotaAlimentaria $c): array => [
                                        $c->getKey() => ($c->concepto->getLabel() ?? $c->concepto->value)
                                            .' · '.($c->periodo ?? 's/p')
                                            .' · saldo '.$c->saldo,
                                    ])
                                    ->all()
                            )
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('monto')
                            ->label('Monto')
                            ->numeric()
                            ->required()
                            ->prefix('₡'),
                        Forms\Components\Select::make('moneda')
                            ->label('Moneda')
                            ->options(Moneda::class)
                            ->default($owner->pensionAlimentaria?->moneda ?? Moneda::CRC)
                            ->required(),
                        Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha de pago')
                            ->required()
                            ->native(false)
                            ->default(now('America/Costa_Rica')),
                        Forms\Components\Select::make('metodo')
                            ->label('Método')
                            ->options(MetodoPago::class)
                            ->default(MetodoPago::Sinpe)
                            ->required(),
                        Forms\Components\TextInput::make('referencia')
                            ->label('Referencia')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('nota')
                            ->label('Nota')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money(fn (PagoAlimentario $record): string => $record->moneda->value),
                Tables\Columns\TextColumn::make('metodo')->label('Método')->badge(),
                Tables\Columns\TextColumn::make('cuota.periodo')->label('Periodo cuota')->placeholder('—'),
                Tables\Columns\TextColumn::make('referencia')->label('Ref.')->placeholder('—')->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Expediente $owner */
                        $owner = $this->getOwnerRecord();
                        $data['despacho_id'] = $owner->despacho_id;
                        $data['pension_alimentaria_id'] = $owner->pensionAlimentaria?->getKey();

                        return $data;
                    })
                    ->after(function (PagoAlimentario $record): void {
                        app(PensionAlimentariaService::class)->registrarPago($record);
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin pagos del obligado')
            ->emptyStateDescription('Registrá SINPE, transferencias u otros pagos contra cada cuota.');
    }
}
