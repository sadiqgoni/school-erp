<?php

namespace App\Filament\Resources\CompiledResults\Pages;

use App\Filament\Resources\CompiledResults\CompiledResultResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompiledResult extends EditRecord
{
    protected static string $resource = CompiledResultResource::class;

    public function getTitle(): string
    {
        return 'Review Compiled Result';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
