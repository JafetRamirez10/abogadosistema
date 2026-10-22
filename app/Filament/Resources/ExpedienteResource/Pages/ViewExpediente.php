<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Actions\ConsultarIa;
use App\Actions\GenerarDocumentoDesdePlantilla;
use App\Actions\GuardarRespuestaIaComoDocumento;
use App\DTOs\BorradorIaData;
use App\Enums\TipoEscritoIa;
use App\Filament\Resources\ExpedienteResource;
use App\Models\Expediente;
use App\Models\Plantilla;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ViewExpediente extends ViewRecord
{
    protected static string $resource = ExpedienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('generarDesdePlantilla')
                ->label('Generar desde plantilla')
                ->icon('heroicon-o-document-plus')
                ->form([
                    Forms\Components\Select::make('plantilla_id')
                        ->label('Plantilla')
                        ->options(fn (): array => Plantilla::query()
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del documento')
                        ->maxLength(255)
                        ->helperText('Opcional. Por defecto usa el nombre de la plantilla.'),
                ])
                ->action(function (array $data): void {
                    /** @var Expediente $expediente */
                    $expediente = $this->getRecord();
                    /** @var User $user */
                    $user = Auth::user();

                    $plantilla = Plantilla::query()->findOrFail($data['plantilla_id']);

                    $documentos = app(GenerarDocumentoDesdePlantilla::class)->handle(
                        expediente: $expediente,
                        plantilla: $plantilla,
                        user: $user,
                        nombre: filled($data['nombre'] ?? null) ? (string) $data['nombre'] : null,
                    );

                    $nombres = collect($documentos)->pluck('nombre')->implode(', ');

                    Notification::make()
                        ->success()
                        ->title('Documento(s) generado(s)')
                        ->body($nombres)
                        ->send();
                }),
            Actions\Action::make('resumirIa')
                ->label('Resumir con IA')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->visible(fn (): bool => Auth::user()?->can('usar_ia') ?? false)
                ->modalHeading('Resumen con IA')
                ->modalDescription('Borrador generado por IA, revisalo antes de usarlo.')
                ->modalSubmitActionLabel('Cerrar / Guardar')
                ->form($this->formularioResultadoIa('Resumen', 'Resumen IA'))
                ->fillForm(function (): array {
                    /** @var Expediente $expediente */
                    $expediente = $this->getRecord();
                    /** @var User $user */
                    $user = Auth::user();

                    try {
                        $consulta = app(ConsultarIa::class)->resumir($expediente, $user);
                    } catch (TooManyRequestsHttpException) {
                        Notification::make()
                            ->danger()
                            ->title('Límite de consultas alcanzado')
                            ->body('Has alcanzado el límite de consultas de IA. Intentá más tarde.')
                            ->send();

                        $this->halt();

                        return [];
                    } catch (Throwable) {
                        Notification::make()
                            ->danger()
                            ->title('No se pudo generar el resumen')
                            ->body('Ocurrió un error al consultar la IA. Intentá de nuevo más tarde.')
                            ->send();

                        $this->halt();

                        return [];
                    }

                    return [
                        'respuesta' => $consulta->respuesta,
                        'nombre_documento' => 'Resumen IA — '.$expediente->numero_interno,
                    ];
                })
                ->action(function (array $data): void {
                    $this->guardarRespuestaIaSiCorresponde($data);
                }),
            Actions\Action::make('borradorIa')
                ->label('Borrador con IA')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn (): bool => Auth::user()?->can('usar_ia') ?? false)
                ->modalHeading('Borrador con IA')
                ->modalDescription('Borrador generado por IA, revisalo antes de usarlo.')
                ->modalSubmitActionLabel('Generar')
                ->form([
                    $this->avisoIaPlaceholder(),
                    Forms\Components\Select::make('tipo_escrito')
                        ->label('Tipo de escrito')
                        ->options(collect(TipoEscritoIa::cases())->mapWithKeys(
                            fn (TipoEscritoIa $t): array => [$t->value => $t->label()]
                        )->all())
                        ->required()
                        ->default(TipoEscritoIa::EscritoVarios->value),
                    Forms\Components\Textarea::make('instrucciones')
                        ->label('Instrucciones')
                        ->rows(6)
                        ->required()
                        ->maxLength(2000)
                        ->helperText('Máximo 2000 caracteres.'),
                    Forms\Components\TextInput::make('tono')
                        ->label('Tono (opcional)')
                        ->maxLength(100)
                        ->placeholder('Formal, firme, conciliador…'),
                ])
                ->action(function (array $data): void {
                    /** @var Expediente $expediente */
                    $expediente = $this->getRecord();
                    /** @var User $user */
                    $user = Auth::user();

                    try {
                        $consulta = app(ConsultarIa::class)->borrador(
                            $expediente,
                            $user,
                            BorradorIaData::fromArray($data),
                        );
                    } catch (TooManyRequestsHttpException) {
                        Notification::make()
                            ->danger()
                            ->title('Límite de consultas alcanzado')
                            ->body('Has alcanzado el límite de consultas de IA. Intentá más tarde.')
                            ->send();

                        return;
                    } catch (Throwable) {
                        Notification::make()
                            ->danger()
                            ->title('No se pudo generar el borrador')
                            ->body('Ocurrió un error al consultar la IA. Intentá de nuevo más tarde.')
                            ->send();

                        return;
                    }

                    $this->mountAction('mostrarRespuestaIa', [
                        'respuesta' => $consulta->respuesta,
                        'nombre_documento' => 'Borrador IA — '.$expediente->numero_interno,
                    ]);
                }),
            Actions\Action::make('mostrarRespuestaIa')
                ->label('Resultado IA')
                ->hidden()
                ->modalHeading('Borrador con IA')
                ->modalDescription('Borrador generado por IA, revisalo antes de usarlo.')
                ->modalSubmitActionLabel('Cerrar / Guardar')
                ->form($this->formularioResultadoIa('Borrador', 'Borrador IA'))
                ->fillForm(fn (array $arguments): array => [
                    'respuesta' => (string) ($arguments['respuesta'] ?? ''),
                    'nombre_documento' => (string) ($arguments['nombre_documento'] ?? 'Borrador IA'),
                ])
                ->action(function (array $data): void {
                    $this->guardarRespuestaIaSiCorresponde($data);
                }),
        ];
    }

    /**
     * @return list<Forms\Components\Component>
     */
    private function formularioResultadoIa(string $labelRespuesta, string $nombreDefault): array
    {
        return [
            $this->avisoIaPlaceholder(),
            Forms\Components\Textarea::make('respuesta')
                ->label($labelRespuesta)
                ->rows(16)
                ->disabled()
                ->dehydrated(),
            Forms\Components\Toggle::make('guardar')
                ->label('Guardar como documento')
                ->default(false)
                ->live(),
            Forms\Components\TextInput::make('nombre_documento')
                ->label('Nombre del documento')
                ->default($nombreDefault)
                ->maxLength(255)
                ->visible(fn (Get $get): bool => (bool) $get('guardar'))
                ->required(fn (Get $get): bool => (bool) $get('guardar')),
        ];
    }

    private function avisoIaPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('aviso_ia')
            ->label('')
            ->content(new HtmlString(
                '<p class="text-sm text-warning-600 dark:text-warning-400">'
                .e('Borrador generado por IA, revisalo antes de usarlo.')
                .'</p>'
            ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardarRespuestaIaSiCorresponde(array $data): void
    {
        if (! ($data['guardar'] ?? false)) {
            return;
        }

        /** @var Expediente $expediente */
        $expediente = $this->getRecord();
        /** @var User $user */
        $user = Auth::user();

        $documento = app(GuardarRespuestaIaComoDocumento::class)->handle(
            expediente: $expediente,
            user: $user,
            contenido: (string) ($data['respuesta'] ?? ''),
            nombre: (string) ($data['nombre_documento'] ?? 'Documento IA'),
        );

        Notification::make()
            ->success()
            ->title('Documento guardado')
            ->body($documento->nombre)
            ->send();
    }
}
