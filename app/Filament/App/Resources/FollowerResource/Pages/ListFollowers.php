<?php

namespace App\Filament\App\Resources\FollowerResource\Pages;

use App\Filament\App\Resources\FollowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFollowers extends ListRecords
{
    protected static string $resource = FollowerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
