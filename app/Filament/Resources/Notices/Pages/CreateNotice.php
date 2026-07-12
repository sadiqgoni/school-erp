<?php

namespace App\Filament\Resources\Notices\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Notices\NoticeResource;
use App\Models\Notice;
use App\Support\OutboundEmail;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateNotice extends CreateRecord
{
    protected static string $resource = NoticeResource::class;

    use RedirectsToIndex;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Filament::auth()->id();

        if (($data['status'] ?? null) === Notice::STATUS_PUBLISHED) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Notice saved — parents in the selected audience can now see it.';
    }

    protected function afterCreate(): void
    {
        app(OutboundEmail::class)->queueNotice($this->record);
    }
}
