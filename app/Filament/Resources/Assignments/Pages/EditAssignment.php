<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Models\Assignment;
use App\Support\OutboundEmail;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    use RedirectsToIndex;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->status === Assignment::STATUS_PUBLISHED && $this->record->wasChanged('status')) {
            app(OutboundEmail::class)->queueAssignment($this->record);
        }
    }
}
