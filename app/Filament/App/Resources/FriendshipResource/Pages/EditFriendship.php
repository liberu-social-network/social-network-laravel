<?php

namespace App\Filament\App\Resources\FriendshipResource\Pages;

use App\Filament\App\Resources\FriendshipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFriendship extends EditRecord
{
    #[\Override]
    protected static string $resource = FriendshipResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
