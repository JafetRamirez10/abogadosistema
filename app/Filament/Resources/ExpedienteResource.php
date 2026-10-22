<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\EstadoExpediente;
use App\Enums\Materia;
use App\Enums\Moneda;
use App\Enums\TipoHonorario;
use App\Filament\Resources\ExpedienteResource\Pages;
use App\Filament\Resources\ExpedienteResource\RelationManagers;
use App\Models\Expediente;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpedienteResource extends Resource
{
    protected static ?string $model = Expediente::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'Casos';

    protected static ?string $modelLabel = 'expediente';

    protected static ?string $pluralModelLabel = 'expedientes';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'numero_interno';

    protected static ?string $tenantOwnershipRelationshipName = 'despacho';

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['numero_interno', 'numero_judicial', 'titulo'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Expediente')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Datos')
                            ->schema([
                                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                                    ->schema([
                                        Forms\Components\Select::make('cliente_id')
                                            ->label('Cliente')
                                            ->relationship('cliente', 'nombre')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Forms\Components\Select::make('responsable_id')
                                            ->label('Responsable')
                                            ->options(fn (): array => User::query()
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->all())
                                            ->searchable()
                                            ->required(),
                                        Forms\Components\TextInput::make('numero_interno')
                                            ->label('N.º interno')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('numero_judicial')
                                            ->label('N.º judicial')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Forms\Components\Select::make('materia')
                                            ->label('Materia')
                                            ->options(Materia::class)
                                            ->required(),
                                        Forms\Components\Select::make('estado')
                                            ->label('Estado')
                                            ->options(EstadoExpediente::class)
                                            ->default(EstadoExpediente::Pendiente)
                                            ->required(),
                                        Forms\Components\TextInput::make('juzgado')
                                            ->label('Juzgado')
                                            ->maxLength(255),
                                        Forms\Components\DatePicker::make('fecha_inicio')
                                            ->label('Fecha de inicio')
                                            ->required()
                                            ->native(false),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Honorarios')
                            ->visible(fn (): bool => auth()->user()?->can('ver_montos') ?? false)
                            ->schema([
                                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                                    ->schema([
                                        Forms\Components\Select::make('tipo_honorario')
                                            ->label('Tipo de honorario')
                                            ->options(TipoHonorario::class)
                                            ->required(),
                                        Forms\Components\Select::make('moneda')
                                            ->label('Moneda')
                                            ->options(Moneda::class)
                                            ->default(Moneda::CRC)
                                            ->required(),
                                        Forms\Components\TextInput::make('cuantia')
                                            ->label('Cuantía')
                                            ->numeric()
                                            ->prefix('₡')
                                            ->visible(fn (): bool => auth()->user()?->can('ver_montos') ?? false),
                                        Forms\Components\TextInput::make('honorario_pactado')
                                            ->label('Honorario pactado')
                                            ->numeric()
                                            ->prefix('₡')
                                            ->visible(fn (): bool => auth()->user()?->can('ver_montos') ?? false),
                                        Forms\Components\TextInput::make('porcentaje_honorario')
                                            ->label('% honorario')
                                            ->numeric()
                                            ->suffix('%')
                                            ->visible(fn (): bool => auth()->user()?->can('ver_montos') ?? false),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Descripción')
                            ->schema([
                                Forms\Components\Textarea::make('descripcion')
                                    ->label('Descripción')
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_interno')
                    ->label('N.º interno')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('materia')
                    ->label('Materia')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->toggleable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('materia')
                    ->label('Materia')
                    ->options(Materia::class),
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoExpediente::class),
                Tables\Filters\SelectFilter::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin expedientes')
            ->emptyStateDescription('Creá el primer caso del despacho.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Nuevo caso'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Datos del expediente')
                    ->schema([
                        Infolists\Components\TextEntry::make('numero_interno')->label('N.º interno'),
                        Infolists\Components\TextEntry::make('numero_judicial')->label('N.º judicial')->placeholder('—'),
                        Infolists\Components\TextEntry::make('titulo')->label('Título')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('cliente.nombre')->label('Cliente'),
                        Infolists\Components\TextEntry::make('responsable.name')->label('Responsable'),
                        Infolists\Components\TextEntry::make('materia')->label('Materia')->badge(),
                        Infolists\Components\TextEntry::make('estado')->label('Estado')->badge(),
                        Infolists\Components\TextEntry::make('juzgado')->label('Juzgado')->placeholder('—'),
                        Infolists\Components\TextEntry::make('fecha_inicio')->label('Fecha de inicio')->date('d/m/Y'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Honorarios')
                    ->visible(fn (): bool => auth()->user()?->can('ver_montos') ?? false)
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo_honorario')->label('Tipo')->badge(),
                        Infolists\Components\TextEntry::make('moneda')->label('Moneda')->badge(),
                        Infolists\Components\TextEntry::make('cuantia')
                            ->label('Cuantía')
                            ->formatStateUsing(fn ($state, Expediente $record): string => $state === null
                                ? '—'
                                : formatear_monto($state, $record->moneda)),
                        Infolists\Components\TextEntry::make('honorario_pactado')
                            ->label('Honorario pactado')
                            ->formatStateUsing(fn ($state, Expediente $record): string => $state === null
                                ? '—'
                                : formatear_monto($state, $record->moneda)),
                        Infolists\Components\TextEntry::make('porcentaje_honorario')->label('% honorario')->suffix('%')->placeholder('—'),
                        Infolists\Components\TextEntry::make('total_pagado')
                            ->label('Total pagado')
                            ->formatStateUsing(fn ($state, Expediente $record): string => formatear_monto($state, $record->moneda)),
                        Infolists\Components\TextEntry::make('saldo_pendiente')
                            ->label('Saldo pendiente')
                            ->formatStateUsing(fn ($state, Expediente $record): string => formatear_monto($state, $record->moneda)),
                        Infolists\Components\TextEntry::make('horas_totales')
                            ->label('Horas totales')
                            ->suffix(' h'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Descripción')
                    ->schema([
                        Infolists\Components\TextEntry::make('descripcion')->label('')->placeholder('Sin descripción.')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PartesRelationManager::class,
            RelationManagers\DocumentosRelationManager::class,
            RelationManagers\ActuacionesRelationManager::class,
            RelationManagers\EventosRelationManager::class,
            RelationManagers\HorasRelationManager::class,
            RelationManagers\PagosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpedientes::route('/'),
            'create' => Pages\CreateExpediente::route('/create'),
            'view' => Pages\ViewExpediente::route('/{record}'),
            'edit' => Pages\EditExpediente::route('/{record}/edit'),
        ];
    }
}
