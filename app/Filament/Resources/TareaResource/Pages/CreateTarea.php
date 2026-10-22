<?php

declare(strict_types=1);

namespace App\Filament\Resources\TareaResource\Pages;

use App\Enums\EstadoTarea;
use App\Filament\Resources\TareaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTarea extends CreateRecord
{
    protected static string $resource = TareaResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creada_por'] = auth()->id();

        if (($data['estado'] ?? null) === EstadoTarea::Completada->value
            || ($data['estado'] ?? null) === EstadoTarea::Completada) {
            $data['completada_at'] = now();
        }

        return $data;
    }
}
