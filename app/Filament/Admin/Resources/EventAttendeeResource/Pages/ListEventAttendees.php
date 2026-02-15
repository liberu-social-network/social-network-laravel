<?php

namespace App\Filament\Admin\Resources\EventAttendeeResource\Pages;

use App\Filament\Admin\Resources\EventAttendeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEventAttendees extends ListRecords
{
    protected static string $resource = EventAttendeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
