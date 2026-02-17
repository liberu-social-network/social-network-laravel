<?php

namespace App\Filament\Admin\Resources\ContentReportResource\Pages;

use App\Filament\Admin\Resources\ContentReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContentReport extends ViewRecord
{
    protected static string $resource = ContentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
