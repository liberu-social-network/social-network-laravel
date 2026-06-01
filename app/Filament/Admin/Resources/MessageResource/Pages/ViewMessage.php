<?php

namespace App\Filament\Admin\Resources\MessageResource\Pages;

use App\Filament\Admin\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMessage extends ViewRecord
{
    #[\Override]
    protected static string $resource = MessageResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
