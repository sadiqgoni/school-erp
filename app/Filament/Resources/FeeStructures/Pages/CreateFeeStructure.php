<?php

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Resources\FeeStructures\Schemas\FeeStructureForm;
use App\Models\FeeStructure;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateFeeStructure extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = FeeStructureResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['school_id'] ??= Filament::getTenant()?->getKey();

        return DB::transaction(function () use ($data): Model {
            $firstRecord = null;

            foreach ($data['fee_items'] ?? [] as $item) {
                $record = FeeStructure::query()->updateOrCreate(
                    [
                        'school_id' => $data['school_id'],
                        'academic_year_id' => $data['academic_year_id'],
                        'term_id' => $data['term_id'] ?? null,
                        'school_class_id' => $data['school_class_id'],
                        'fee_type_id' => $item['fee_type_id'],
                    ],
                    [
                        'amount' => FeeStructureForm::sanitizeAmount($item['amount'] ?? 0),
                        'due_date' => null,
                        'is_active' => $data['is_active'] ?? true,
                    ],
                );

                $firstRecord ??= $record;
            }

            return $firstRecord ?? FeeStructure::query()->create([
                'school_id' => $data['school_id'],
                'academic_year_id' => $data['academic_year_id'],
                'term_id' => $data['term_id'] ?? null,
                'school_class_id' => $data['school_class_id'],
                'fee_type_id' => $data['fee_items'][0]['fee_type_id'] ?? null,
                'amount' => 0,
                'due_date' => null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }
}
