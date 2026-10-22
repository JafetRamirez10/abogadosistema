<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TipoCliente;
use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use App\Support\Cedula;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $tenantOwnershipRelationshipName = 'despacho';

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'email'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del cliente')
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo')
                                    ->options(TipoCliente::class)
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('cedula')
                                    ->label('Cédula')
                                    ->required()
                                    ->helperText(fn (Forms\Get $get): string => $get('tipo') === TipoCliente::Juridica->value
                                            || $get('tipo') === TipoCliente::Juridica
                                        ? 'Persona jurídica: 10 dígitos sin guiones.'
                                        : 'Persona física: 9 dígitos sin guiones.')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('telefono')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('direccion')
                                    ->label('Dirección')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('notas')
                                    ->label('Notas')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('nombre')
                            ->label('Nombre')
                            ->sortable()
                            ->weight('bold'),
                        Tables\Columns\TextColumn::make('cedula')
                            ->label('Cédula')
                            ->formatStateUsing(fn (?string $state): string => Cedula::enmascarar($state))
                            ->color('gray'),
                    ]),
                    Tables\Columns\TextColumn::make('tipo')
                        ->label('Tipo')
                        ->badge()
                        ->visibleFrom('md'),
                    Tables\Columns\TextColumn::make('telefono')
                        ->label('Teléfono')
                        ->placeholder('—')
                        ->visibleFrom('md'),
                    Tables\Columns\TextColumn::make('email')
                        ->label('Correo')
                        ->placeholder('—')
                        ->toggleable()
                        ->visibleFrom('md'),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(TipoCliente::class),
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
            ->emptyStateHeading('Sin clientes')
            ->emptyStateDescription('Creá el primer cliente del despacho para comenzar.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo cliente'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Cliente')
                    ->schema([
                        Infolists\Components\TextEntry::make('nombre')->label('Nombre'),
                        Infolists\Components\TextEntry::make('tipo')->label('Tipo')->badge(),
                        Infolists\Components\TextEntry::make('cedula')
                            ->label('Cédula')
                            ->formatStateUsing(fn (?string $state): string => Cedula::enmascarar($state)),
                        Infolists\Components\TextEntry::make('telefono')->label('Teléfono')->placeholder('—'),
                        Infolists\Components\TextEntry::make('email')->label('Correo')->placeholder('—'),
                        Infolists\Components\TextEntry::make('direccion')->label('Dirección')->placeholder('—'),
                        Infolists\Components\TextEntry::make('notas')->label('Notas')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Expedientes')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('expedientes')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('numero_interno')->label('N.º interno'),
                                Infolists\Components\TextEntry::make('titulo')->label('Título'),
                                Infolists\Components\TextEntry::make('materia')->label('Materia')->badge(),
                                Infolists\Components\TextEntry::make('estado')->label('Estado')->badge(),
                            ])
                            ->columns(4)
                            ->placeholder('Este cliente aún no tiene expedientes.'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'view' => Pages\ViewCliente::route('/{record}'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
