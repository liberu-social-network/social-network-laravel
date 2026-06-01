<?php

namespace App\Filament\Admin\Resources\CommentResource\Pages;

use App\Filament\Admin\Resources\CommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComments extends ListRecords
{
    #[\Override]
    protected static string $resource = CommentResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
