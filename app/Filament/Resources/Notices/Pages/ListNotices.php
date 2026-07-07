<?php

namespace App\Filament\Resources\Notices\Pages;

use App\Filament\Resources\Notices\NoticeResource;
use App\Support\TeacherWorkspace;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotices extends ListRecords
{
    protected static string $resource = NoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(TeacherWorkspace::isTeacher() ? 'New class announcement' : 'New notice')
                ->icon('heroicon-o-plus'),
        ];
    }
}
