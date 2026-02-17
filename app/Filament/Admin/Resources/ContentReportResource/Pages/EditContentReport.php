<?php

namespace App\Filament\Admin\Resources\ContentReportResource\Pages;

use App\Filament\Admin\Resources\ContentReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentReport extends EditRecord
{
    protected static string $resource = ContentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set reviewed_by and reviewed_at when status changes
        if (isset($data['status']) && $data['status'] !== $this->record->status) {
            $data['reviewed_by'] = auth()->id();
            $data['reviewed_at'] = now();
        }

        return $data;
    }
}
