<?php

namespace App\Filament\Resources\Notices\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Notices\NoticeResource;
use App\Models\Notice;
use App\Support\OutboundEmail;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotice extends EditRecord
{
    protected static string $resource = NoticeResource::class;

    use RedirectsToIndex;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === Notice::STATUS_PUBLISHED && ! $this->getRecord()->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->status === Notice::STATUS_PUBLISHED && $this->record->wasChanged('published_at')) {
            app(OutboundEmail::class)->queueNotice($this->record);
        }
    }
}
