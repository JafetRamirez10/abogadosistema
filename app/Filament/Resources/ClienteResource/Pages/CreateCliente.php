<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Actions\CrearCliente;
use App\DTOs\ClienteData;
use App\Filament\Resources\ClienteResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant();

        if ($tenant !== null) {
            $data['despacho_id'] = $tenant->getKey();
        }

        return app(CrearCliente::class)->handle(ClienteData::fromArray($data));
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Cliente creado';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
