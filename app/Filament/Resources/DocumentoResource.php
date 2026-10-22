<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\EstadoDocumento;
use App\Filament\Resources\DocumentoResource\Pages;
use App\Models\Documento;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentoResource extends Resource
{
    protected static ?string $model = Documento::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Documentos';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $tenantOwnershipRelationshipName = 'despacho';

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre'];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expediente.numero_interno')
                    ->label('Expediente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version_actual')
                    ->label('Versión')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('ultimaVersion.created_at')
                    ->label('Última versión')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('plantilla.nombre')
                    ->label('Plantilla')
                    ->toggleable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoDocumento::class),
            ])
            ->actions([
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Documento $record): string => route('documentos.firmar-descarga', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Documento $record): bool => $record->ultimaVersion !== null),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Sin documentos')
            ->emptyStateDescription('Los documentos se generan o suben desde cada expediente.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentos::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
