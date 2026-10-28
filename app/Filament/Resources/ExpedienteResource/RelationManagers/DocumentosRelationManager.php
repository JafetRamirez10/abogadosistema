<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Actions\SubirVersionDocumento;
use App\Enums\EstadoDocumento;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    protected static ?string $title = 'Documentos';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('estado')
                    ->label('Estado')
                    ->options(EstadoDocumento::class)
                    ->default(EstadoDocumento::PendienteRevision)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version_actual')
                    ->label('v.')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('ultimaVersion.user.name')
                    ->label('Subió')
                    ->toggleable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('subirDocumento')
                    ->label('Subir documento')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('archivo')
                            ->label('Archivo')
                            ->required()
                            ->disk('local')
                            ->directory('tmp-uploads')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'audio/mpeg',
                                'audio/mp4',
                                'audio/wav',
                                'audio/webm',
                                'video/mp4',
                                'video/webm',
                            ])
                            ->maxSize(51200)
                            ->helperText('PDF, Word, imágenes, audio o video (máx. 50 MB).'),
                        Forms\Components\TextInput::make('nota')
                            ->label('Nota')
                            ->maxLength(255),
                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options(EstadoDocumento::class)
                            ->default(EstadoDocumento::PendienteRevision)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        /** @var Expediente $expediente */
                        $expediente = $this->getOwnerRecord();
                        /** @var User $user */
                        $user = Auth::user();

                        $uploaded = $this->toUploadedFile($data['archivo'] ?? null);
                        if ($uploaded === null) {
                            Notification::make()->danger()->title('Archivo inválido')->send();

                            return;
                        }

                        app(SubirVersionDocumento::class)->handle(
                            expediente: $expediente,
                            archivo: $uploaded,
                            user: $user,
                            meta: [
                                'nombre' => $data['nombre'],
                                'nota' => $data['nota'] ?? null,
                                'estado' => $data['estado'] ?? EstadoDocumento::PendienteRevision,
                            ],
                        );

                        Notification::make()
                            ->success()
                            ->title('Documento subido')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('subirVersion')
                    ->label('Nueva versión')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('archivo')
                            ->label('Archivo')
                            ->required()
                            ->disk('local')
                            ->directory('tmp-uploads')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'audio/mpeg',
                                'audio/mp4',
                                'audio/wav',
                                'audio/webm',
                                'video/mp4',
                                'video/webm',
                            ])
                            ->maxSize(51200)
                            ->helperText('PDF, Word, imágenes, audio o video (máx. 50 MB).'),
                        Forms\Components\TextInput::make('nota')
                            ->label('Nota')
                            ->maxLength(255),
                    ])
                    ->action(function (Documento $record, array $data): void {
                        /** @var Expediente $expediente */
                        $expediente = $this->getOwnerRecord();
                        /** @var User $user */
                        $user = Auth::user();

                        $uploaded = $this->toUploadedFile($data['archivo'] ?? null);
                        if ($uploaded === null) {
                            Notification::make()->danger()->title('Archivo inválido')->send();

                            return;
                        }

                        $documento = app(SubirVersionDocumento::class)->handle(
                            expediente: $expediente,
                            archivo: $uploaded,
                            user: $user,
                            documento: $record,
                            meta: [
                                'nota' => $data['nota'] ?? null,
                            ],
                        );

                        Notification::make()
                            ->success()
                            ->title('Versión subida')
                            ->body('Ahora es la v'.$documento->version_actual)
                            ->send();
                    }),
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Documento $record): string => route('documentos.firmar-descarga', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Documento $record): bool => $record->ultimaVersion !== null),
                Tables\Actions\Action::make('historial')
                    ->label('Historial')
                    ->icon('heroicon-o-clock')
                    ->modalHeading(fn (Documento $record): string => 'Historial — '.$record->nombre)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function (Documento $record) {
                        $versiones = $record->versiones()->with('user')->orderByDesc('version')->get();

                        return view('filament.documentos.historial-versiones', [
                            'versiones' => $versiones,
                            'documento' => $record,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin documentos')
            ->emptyStateDescription('Subí un archivo o generá uno desde una plantilla.');
    }

    private function toUploadedFile(mixed $archivo): ?UploadedFile
    {
        if ($archivo instanceof UploadedFile) {
            return $archivo;
        }

        if (! is_string($archivo) || $archivo === '') {
            return null;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($archivo)) {
            return null;
        }

        $fullPath = $disk->path($archivo);

        return new UploadedFile(
            $fullPath,
            basename($archivo),
            $disk->mimeType($archivo) ?: null,
            null,
            true,
        );
    }
}
