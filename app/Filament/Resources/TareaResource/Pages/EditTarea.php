<?php

declare(strict_types=1);

namespace App\Filament\Resources\TareaResource\Pages;

use App\Enums\EstadoTarea;
use App\Filament\Resources\TareaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTarea extends EditRecord
{
    protected static string $resource = TareaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $estado = $data['estado'] ?? null;

        if ($estado === EstadoTarea::Completada->value || $estado === EstadoTarea::Completada) {
            $data['completada_at'] ??= now();
        } else {
            $data['completada_at'] = null;
        }

        return $data;
    }
}
