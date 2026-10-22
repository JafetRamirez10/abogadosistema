<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Filament\Resources\TareaResource\Pages;
use App\Models\Tarea;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TareaResource extends Resource
{
    protected static ?string $model = Tarea::class;

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = 'Tareas';

    protected static ?string $modelLabel = 'tarea';

    protected static ?string $pluralModelLabel = 'tareas';

    protected static ?int $navigationSort = 6;

    protected static ?string $tenantOwnershipRelationshipName = 'despacho';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalle')
                    ->schema([
                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Escribí el detalle de la tarea…')
                            ->required()
                            ->rows(10)
                            ->autosize()
                            ->maxLength(10000)
                            ->columnSpanFull()
                            ->extraInputAttributes([
                                'class' => 'min-h-[14rem] text-base leading-relaxed',
                            ]),
                    ]),
                Forms\Components\Section::make('Asignación')
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Forms\Components\Select::make('asignada_a')
                                    ->label('Asignada a')
                                    ->options(fn (): array => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->default(fn (): mixed => auth()->id()),
                                Forms\Components\Select::make('expediente_id')
                                    ->label('Expediente')
                                    ->relationship('expediente', 'numero_interno')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Forms\Components\DatePicker::make('vence_el')
                                    ->label('Vence el')
                                    ->native(false),
                                Forms\Components\Select::make('prioridad')
                                    ->label('Prioridad')
                                    ->options(PrioridadTarea::class)
                                    ->default(PrioridadTarea::Media)
                                    ->required(),
                                Forms\Components\Select::make('estado')
                                    ->label('Columna')
                                    ->options(EstadoTarea::class)
                                    ->default(EstadoTarea::Pendiente)
                                    ->required()
                                    ->live(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap()
                    ->limit(60),
                Tables\Columns\TextColumn::make('asignada.name')
                    ->label('Asignada a')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expediente.numero_interno')
                    ->label('Expediente')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vence_el')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('prioridad')
                    ->label('Prioridad')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options(EstadoTarea::class),
                Tables\Filters\SelectFilter::make('prioridad')
                    ->options(PrioridadTarea::class),
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
            ->emptyStateHeading('Sin tareas')
            ->emptyStateDescription('Creá tareas para el seguimiento diario del despacho.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTareas::route('/'),
        ];
    }
}
