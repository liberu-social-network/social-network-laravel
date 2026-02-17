<?php

namespace App\Filament\Admin\Resources\ContentReportResource\Pages;

use App\Filament\Admin\Resources\ContentReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentReports extends ListRecords
{
    protected static string $resource = ContentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
