<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    #[\Override]
    protected static string $resource = UserResource::class;

    #[\Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure email_verified_at is set for new users if not specified
        if (empty($data['email_verified_at'])) {
            $data['email_verified_at'] = null;
        }

        return $data;
    }
}
