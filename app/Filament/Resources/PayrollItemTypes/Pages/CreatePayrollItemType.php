<?php

namespace App\Filament\Resources\PayrollItemTypes\Pages;

use App\Filament\Resources\PayrollItemTypes\PayrollItemTypeResource;
use App\Models\LedgerAccount;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePayrollItemType extends CreateRecord
{
    protected static string $resource = PayrollItemTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return static::normalizePayrollItemData($data);
    }

    public static function normalizePayrollItemData(array $data): array
    {
        $details = $data['calculation_details'] ?? [];

        $data['value'] = match ($data['calculation_type'] ?? null) {
            'fixed_amount',
            'percentage_of_gross',
            'percentage_of_item',
            'percentage_of_gross_with_exclusions' => (float) ($details['value'] ?? 0),
            'percentage_of_sum' => (float) ($details['percentage'] ?? 0),
            'anniversary_based' => match ($details['amount_method'] ?? null) {
                'fixed' => (float) ($details['fixed_amount'] ?? 0),
                'percentage_of_basic' => (float) ($details['percentage_value'] ?? 0),
                default => 0,
            },
            'leave_grant' => 20.0,
            default => (float) ($data['value'] ?? 0),
        };

        $data['code'] = static::resolveCode($data);

        $data['salary_template_id'] = null;
        $data['grade_level'] = null;
        $data['step'] = null;

        return $data;
    }

    protected static function resolveCode(array $data): string
    {
        $ledgerAccountId = $data['ledger_account_id'] ?? null;

        if ($ledgerAccountId) {
            $account = LedgerAccount::query()->find($ledgerAccountId);

            if ($account) {
                return $account->code;
            }
        }

        return Str::upper(Str::slug((string) ($data['name'] ?? 'PAYROLL-ELEMENT'), '-'));
    }
}
