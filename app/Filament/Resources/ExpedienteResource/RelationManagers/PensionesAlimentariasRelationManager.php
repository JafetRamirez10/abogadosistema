<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Enums\Materia;
use App\Enums\Moneda;
use App\Models\Expediente;
use App\Models\PensionAlimentaria;
use App\Services\PensionAlimentariaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PensionesAlimentariasRelationManager extends RelationManager
{
    protected static string $relationship = 'pensionesAlimentarias';

    protected static ?string $title = 'Pensión alimentaria';

    protected static ?string $modelLabel = 'pensión';

    protected static ?string $pluralModelLabel = 'pensión';

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
                        Forms\Components\TextInput::make('cuota_mensual')
                            ->label('Cuota mensual')
                            ->numeric()
                            ->required()
                            ->prefix('₡'),
                        Forms\Components\Select::make('moneda')
                            ->label('Moneda')
                            ->options(Moneda::class)
                            ->default(Moneda::CRC)
                            ->required(),
                        Forms\Components\TextInput::make('dia_vencimiento')
                            ->label('Día de vencimiento')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->default(1)
                            ->required()
                            ->helperText('Día del mes (1–28) en que vence la cuota.'),
                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->required()
                            ->native(false)
                            ->default(now('America/Costa_Rica')),
                        Forms\Components\Toggle::make('incluye_aguinaldo')
                            ->label('Incluye aguinaldo')
                            ->default(true),
                        Forms\Components\Toggle::make('incluye_salario_escolar')
                            ->label('Incluye salario escolar')
                            ->default(true),
                        Forms\Components\TextInput::make('monto_aguinaldo')
                            ->label('Monto aguinaldo')
                            ->numeric()
                            ->prefix('₡'),
                        Forms\Components\TextInput::make('monto_salario_escolar')
                            ->label('Monto salario escolar')
                            ->numeric()
                            ->prefix('₡'),
                        Forms\Components\Toggle::make('activa')
                            ->label('Pensión activa')
                            ->default(true),
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
            ->recordTitleAttribute('cuota_mensual')
            ->columns([
                Tables\Columns\TextColumn::make('cuota_mensual')
                    ->label('Cuota mensual')
                    ->money(fn (PensionAlimentaria $record): string => $record->moneda->value),
                Tables\Columns\TextColumn::make('dia_vencimiento')->label('Día vence'),
                Tables\Columns\IconColumn::make('activa')->label('Activa')->boolean(),
                Tables\Columns\IconColumn::make('incluye_aguinaldo')->label('Aguinaldo')->boolean(),
                Tables\Columns\IconColumn::make('incluye_salario_escolar')->label('Escolar')->boolean(),
                Tables\Columns\TextColumn::make('fecha_inicio')->label('Inicio')->date('d/m/Y'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->pensionesAlimentarias()->doesntExist())
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Expediente $owner */
                        $owner = $this->getOwnerRecord();
                        $data['despacho_id'] = $owner->despacho_id;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generarCuotaMes')
                    ->label('Generar cuota del mes')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function (PensionAlimentaria $record): void {
                        $cuota = app(PensionAlimentariaService::class)->generarCuotaMensual($record);
                        Notification::make()
                            ->success()
                            ->title('Cuota lista')
                            ->body('Periodo '.$cuota->periodo.' · vence '.$cuota->vence_el->format('d/m/Y'))
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Sin pensión configurada')
            ->emptyStateDescription('Definí la cuota mensual, aguinaldo y salario escolar del caso.');
    }
}
