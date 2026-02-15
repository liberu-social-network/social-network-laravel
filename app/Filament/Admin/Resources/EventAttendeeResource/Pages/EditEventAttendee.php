<?php

namespace App\Filament\Admin\Resources\EventAttendeeResource\Pages;

use App\Filament\Admin\Resources\EventAttendeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEventAttendee extends EditRecord
{
    protected static string $resource = EventAttendeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
