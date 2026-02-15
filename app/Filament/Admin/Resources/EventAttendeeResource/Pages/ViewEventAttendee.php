<?php

namespace App\Filament\Admin\Resources\EventAttendeeResource\Pages;

use App\Filament\Admin\Resources\EventAttendeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEventAttendee extends ViewRecord
{
    protected static string $resource = EventAttendeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
