<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Actions\CrearExpediente;
use App\DTOs\ExpedienteData;
use App\Filament\Resources\ExpedienteResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExpediente extends CreateRecord
{
    protected static string $resource = ExpedienteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant();

        if ($tenant !== null) {
            $data['despacho_id'] = $tenant->getKey();
        }

        return app(CrearExpediente::class)->handle(ExpedienteData::fromArray($data));
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Expediente creado';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
