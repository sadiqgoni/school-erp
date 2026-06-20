<?php

namespace App\Filament\Resources\PayrollSnapshots\Pages;

use App\Filament\Resources\PayrollSnapshots\PayrollSnapshotResource;
use App\Models\PayrollItemType;
use App\Models\Staff;
use App\Models\StaffSalaryAdjustment;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPayrollSnapshot extends ViewRecord
{
    protected static string $resource = PayrollSnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previous')
                ->label('')
                ->icon(Heroicon::OutlinedChevronLeft)
                ->color('gray')
                ->disabled(fn (): bool => $this->getAdjacentRecord('previous') === null)
                ->url(fn (): ?string => ($record = $this->getAdjacentRecord('previous')) ? PayrollSnapshotResource::getUrl('view', ['record' => $record]) : null),
            Action::make('next')
                ->label('')
                ->icon(Heroicon::OutlinedChevronRight)
                ->color('gray')
                ->disabled(fn (): bool => $this->getAdjacentRecord('next') === null)
                ->url(fn (): ?string => ($record = $this->getAdjacentRecord('next')) ? PayrollSnapshotResource::getUrl('view', ['record' => $record]) : null),
            $this->adjustmentAction('allowance'),
            $this->adjustmentAction('deduction'),
        ];
    }

    protected function adjustmentAction(string $type): Action
    {
        $label = $type === 'allowance' ? 'Add earning' : 'Add deduction';

        return Action::make("add_{$type}")
            ->label($label)
            ->color($type === 'allowance' ? 'success' : 'danger')
            ->modalWidth('sm')
            ->schema([
                Select::make('item_id')
                    ->label($type === 'allowance' ? 'Earning rule' : 'Deduction rule')
                    ->options(fn (): array => PayrollItemType::query()
                        ->where('school_id', Filament::getTenant()?->getKey())
                        ->where('type', $type)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($item): array => [$item->getKey() => "{$item->name} ({$item->code})"])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('override_value')
                    ->label('Override value')
                    ->numeric(),
                Textarea::make('notes'),
            ])
            ->action(function (array $data) use ($type): void {
                $item = PayrollItemType::query()
                    ->where('school_id', $this->record->school_id)
                    ->where('type', $type)
                    ->findOrFail($data['item_id']);

                StaffSalaryAdjustment::query()->updateOrCreate(
                    [
                        'staff_id' => $this->record->getKey(),
                        'type' => $type,
                        'code' => $item->code,
                    ],
                    [
                        'school_id' => $this->record->school_id,
                        'ledger_account_id' => $item->ledger_account_id,
                        'name' => $item->name,
                        'calculation_type' => $item->calculation_type,
                        'value' => filled($data['override_value'] ?? null) ? $data['override_value'] : $item->value,
                        'notes' => $data['notes'] ?? null,
                        'is_active' => true,
                    ],
                );

                Notification::make()
                    ->success()
                    ->title($type === 'allowance' ? 'Earning added' : 'Deduction added')
                    ->send();
            });
    }

    protected function getAdjacentRecord(string $direction): ?Staff
    {
        $current = Staff::query()
            ->whereKey($this->record->getKey())
            ->select(['id', 'school_id', 'first_name'])
            ->first();

        if (! $current) {
            return null;
        }

        $query = Staff::query()
            ->where('school_id', $current->school_id)
            ->orderBy('first_name')
            ->orderBy('id');

        if ($direction === 'previous') {
            return $query
                ->where(function ($query) use ($current): void {
                    $query->where('first_name', '<', $current->first_name)
                        ->orWhere(function ($query) use ($current): void {
                            $query->where('first_name', $current->first_name)
                                ->where('id', '<', $current->id);
                        });
                })
                ->orderByDesc('first_name')
                ->orderByDesc('id')
                ->first();
        }

        return $query
            ->where(function ($query) use ($current): void {
                $query->where('first_name', '>', $current->first_name)
                    ->orWhere(function ($query) use ($current): void {
                        $query->where('first_name', $current->first_name)
                            ->where('id', '>', $current->id);
                    });
            })
            ->first();
    }
}
