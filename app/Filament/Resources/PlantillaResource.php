<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\DTOs\VariablesPlantillaData;
use App\Enums\FormatoSalidaPlantilla;
use App\Enums\Materia;
use App\Filament\Resources\PlantillaResource\Pages;
use App\Models\Plantilla;
use App\Support\PlantillaRenderer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class PlantillaResource extends Resource
{
    protected static ?string $model = Plantilla::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Plantillas';

    protected static ?string $modelLabel = 'plantilla';

    protected static ?string $pluralModelLabel = 'plantillas';

    protected static ?int $navigationSort = 9;

    protected static ?string $tenantOwnershipRelationshipName = 'despacho';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plantilla')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('materia')
                            ->label('Materia')
                            ->options(Materia::class)
                            ->nullable(),
                        Forms\Components\Select::make('formato_salida')
                            ->label('Formato de salida')
                            ->options(FormatoSalidaPlantilla::class)
                            ->default(FormatoSalidaPlantilla::Ambos)
                            ->required(),
                        Forms\Components\MarkdownEditor::make('contenido')
                            ->label('Contenido')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Usá variables con la sintaxis {{ variable }}.')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'heading',
                                'italic',
                                'orderedList',
                                'redo',
                                'undo',
                            ]),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Variables disponibles')
                    ->schema([
                        Forms\Components\Placeholder::make('variables_panel')
                            ->label('')
                            ->content(fn (): HtmlString => new HtmlString(
                                '<ul class="list-disc pl-5 text-sm space-y-1">'
                                .collect(PlantillaRenderer::VARIABLES_PERMITIDAS)
                                    ->map(fn (string $v): string => '<li><code>{{ '.$v.' }}</code></li>')
                                    ->implode('')
                                .'</ul>'
                            )),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('materia')
                    ->label('Materia')
                    ->badge()
                    ->placeholder('Todas'),
                Tables\Columns\TextColumn::make('formato_salida')
                    ->label('Formato')
                    ->badge(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('materia')
                    ->label('Materia')
                    ->options(Materia::class),
                Tables\Filters\SelectFilter::make('formato_salida')
                    ->label('Formato')
                    ->options(FormatoSalidaPlantilla::class),
            ])
            ->actions([
                Tables\Actions\Action::make('vistaPrevia')
                    ->label('Vista previa')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Vista previa')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function (Plantilla $record): HtmlString {
                        $ejemplo = collect(PlantillaRenderer::VARIABLES_PERMITIDAS)
                            ->mapWithKeys(fn (string $key): array => [$key => '['.$key.']'])
                            ->all();
                        $render = app(PlantillaRenderer::class)->render(
                            $record->contenido,
                            new VariablesPlantillaData($ejemplo),
                            escapeHtml: true,
                        );

                        return new HtmlString(
                            '<div class="prose dark:prose-invert max-w-none whitespace-pre-wrap text-sm">'
                            .nl2br($render)
                            .'</div>'
                        );
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin plantillas')
            ->emptyStateDescription('Creá plantillas reutilizables para generar documentos.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Nueva plantilla'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlantillas::route('/'),
            'create' => Pages\CreatePlantilla::route('/create'),
            'edit' => Pages\EditPlantilla::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('gestionar_plantillas') ?? false;
    }
}
