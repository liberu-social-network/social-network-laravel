<?php

namespace App\Filament\App\Resources\FriendshipResource\Pages;

use App\Filament\App\Resources\FriendshipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFriendships extends ListRecords
{
    protected static string $resource = FriendshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
