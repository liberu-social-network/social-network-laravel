<?php

namespace App\Filament\App\Resources\FollowerResource\Pages;

use App\Filament\App\Resources\FollowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFollowers extends ListRecords
{
    #[\Override]
    protected static string $resource = FollowerResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
