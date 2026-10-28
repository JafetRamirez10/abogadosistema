<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Enums\EstadoMedidaCautelar;
use App\Enums\Materia;
use App\Enums\TipoMedidaCautelar;
use App\Models\Expediente;
use App\Models\MedidaCautelar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MedidasCautelaresRelationManager extends RelationManager
{
    protected static string $relationship = 'medidasCautelares';

    protected static ?string $title = 'Medidas cautelares';

    protected static ?string $modelLabel = 'medida cautelar';

    protected static ?string $pluralModelLabel = 'medidas cautelares';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Expediente
            && $ownerRecord->materia === Materia::Penal;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options(TipoMedidaCautelar::class)
                            ->required(),
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options(EstadoMedidaCautelar::class)
                            ->default(EstadoMedidaCautelar::Vigente)
                            ->required(),
                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('fecha_revision')
                            ->label('Próxima revisión')
                            ->native(false),
                        Forms\Components\DatePicker::make('fecha_vencimiento')
                            ->label('Vencimiento')
                            ->native(false),
                        Forms\Components\TextInput::make('autoridad')
                            ->label('Autoridad')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_inicio', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge(),
                Tables\Columns\TextColumn::make('fecha_inicio')->label('Inicio')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('fecha_revision')
                    ->label('Revisión')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color(fn (MedidaCautelar $record): string => $record->requiere_atencion ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('fecha_vencimiento')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color(fn (MedidaCautelar $record): string => $record->requiere_atencion ? 'danger' : 'gray'),
                Tables\Columns\IconColumn::make('requiere_atencion')->label('Atención')->boolean(),
                Tables\Columns\TextColumn::make('autoridad')->label('Autoridad')->placeholder('—')->toggleable(),
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
            ->emptyStateHeading('Sin medidas cautelares')
            ->emptyStateDescription('Registrá prisión preventiva, firmas, cauciones y sus revisiones.');
    }
}
