<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Enums\EstadoEvento;
use App\Enums\TipoEvento;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EventosRelationManager extends RelationManager
{
    protected static string $relationship = 'eventos';

    protected static ?string $title = 'Eventos';

    protected static ?string $modelLabel = 'evento';

    protected static ?string $pluralModelLabel = 'eventos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options(TipoEvento::class)
                            ->required(),
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options(EstadoEvento::class)
                            ->default(EstadoEvento::Pendiente)
                            ->required(),
                        Forms\Components\DateTimePicker::make('inicio')
                            ->label('Inicio')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('fin')
                            ->label('Fin')
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\Select::make('responsable_id')
                            ->label('Responsable')
                            ->options(fn (): array => User::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->default(fn (): mixed => auth()->id()),
                        Forms\Components\TextInput::make('lugar')
                            ->label('Lugar')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('dias_aviso')
                            ->label('Días de aviso')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30)
                            ->default(3)
                            ->required(),
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
            ->recordTitleAttribute('titulo')
            ->defaultSort('inicio')
            ->columns([
                Tables\Columns\TextColumn::make('inicio')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('semaforo_badge')
                    ->label('Semáforo')
                    ->badge()
                    ->color(fn (Evento $record): string => $record->semaforo->color()),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->toggleable()
                    ->visibleFrom('md'),
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
            ->emptyStateHeading('Sin eventos')
            ->emptyStateDescription('Agendá audiencias, vencimientos y citas del expediente.');
    }
}
