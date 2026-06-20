<?php

namespace App\Support;

use App\Models\PayrollItemType;
use App\Models\SalaryPosting;
use App\Models\Staff;
use App\Models\StaffSalaryAdjustment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayrollCalculator
{
    public static function snapshotForStaff(Staff $staff): array
    {
        $staff->loadMissing('salaryTemplate');

        $basicSalary = (float) ($staff->basic_salary ?: $staff->salaryTemplate?->monthly_basic ?: 0);

        $earningRules = self::mergedItemsFor($staff, PayrollItemType::TYPE_ALLOWANCE);
        $earnings = self::calculateItems($earningRules, $staff, $basicSalary, true);
        $allowancesTotal = round(collect($earnings)->sum('amount'), 2);
        $grossPay = round($basicSalary + $allowancesTotal, 2);

        $deductionRules = self::mergedItemsFor($staff, PayrollItemType::TYPE_DEDUCTION);
        $deductions = self::calculateItems($deductionRules, $staff, $basicSalary, false, $earnings, $grossPay);
        $deductionsTotal = round(collect($deductions)->sum('amount'), 2);

        return [
            'basic_salary' => round($basicSalary, 2),
            'allowances' => $earnings,
            'allowances_total' => $allowancesTotal,
            'gross_pay' => $grossPay,
            'deductions' => $deductions,
            'deductions_total' => $deductionsTotal,
            'net_pay' => round($grossPay - $deductionsTotal, 2),
        ];
    }

    public static function postingData(Staff $staff, string $month, ?int $userId = null): array
    {
        $staff->loadMissing('salaryTemplate');
        $snapshot = self::snapshotForStaff($staff);
        $payrollMonth = Carbon::parse($month)->startOfMonth();

        return [
            'school_id' => $staff->school_id,
            'staff_id' => $staff->getKey(),
            'salary_template_id' => $staff->salary_template_id,
            'payroll_month' => $payrollMonth->toDateString(),
            'reference' => 'PAY-'.$payrollMonth->format('Ym').'-'.$staff->getKey(),
            'staff_number' => $staff->staff_number,
            'staff_name' => $staff->full_name,
            'grade_level' => $staff->salary_grade_level ?: $staff->salaryTemplate?->grade_level,
            'step' => $staff->salary_step ?: $staff->salaryTemplate?->step,
            'basic_salary' => $snapshot['basic_salary'],
            'allowances_total' => $snapshot['allowances_total'],
            'gross_pay' => $snapshot['gross_pay'],
            'deductions_total' => $snapshot['deductions_total'],
            'net_pay' => $snapshot['net_pay'],
            'allowance_breakdown' => $snapshot['allowances'],
            'deduction_breakdown' => $snapshot['deductions'],
            'status' => SalaryPosting::query()
                ->where('school_id', $staff->school_id)
                ->where('staff_id', $staff->getKey())
                ->whereDate('payroll_month', $payrollMonth->toDateString())
                ->value('status') ?: 'posted',
            'posted_at' => now(),
            'posted_by_id' => $userId,
        ];
    }

    protected static function mergedItemsFor(Staff $staff, string $type): Collection
    {
        $rules = self::payrollItemsFor($staff, $type)
            ->map(fn (PayrollItemType $item): array => self::mapPayrollItem($item, 'rule'))
            ->keyBy('code');

        $adjustments = self::adjustmentsFor($staff, $type)
            ->map(fn (StaffSalaryAdjustment $item): array => self::mapAdjustment($item))
            ->keyBy('code');

        return $rules->merge($adjustments)->values();
    }

    protected static function mapPayrollItem(PayrollItemType $item, string $source): array
    {
        return [
            'id' => (string) $item->getKey(),
            'source' => $source,
            'code' => $item->code,
            'name' => $item->name,
            'calculation_type' => $item->calculation_type,
            'value' => (float) $item->value,
            'calculation_details' => $item->calculation_details ?? [],
            'ledger_account' => $item->ledgerAccount?->name,
        ];
    }

    protected static function mapAdjustment(StaffSalaryAdjustment $item): array
    {
        return [
            'id' => 'adjustment-'.$item->getKey(),
            'source' => 'staff',
            'code' => $item->code,
            'name' => $item->name,
            'calculation_type' => $item->calculation_type,
            'value' => (float) $item->value,
            'calculation_details' => [],
            'ledger_account' => $item->ledgerAccount?->name,
        ];
    }

    protected static function calculateItems(
        Collection $items,
        Staff $staff,
        float $basicSalary,
        bool $isEarning,
        array $earningResults = [],
        ?float $grossPay = null,
    ): array {
        $items = $items->values();
        $resolved = [];
        $deferred = [];

        foreach ($items as $item) {
            if (in_array($item['calculation_type'], ['percentage_of_gross', 'percentage_of_gross_with_exclusions'], true)) {
                $deferred[] = $item;
                continue;
            }

            $resolved[] = $item + [
                'amount' => self::resolveAmount($item, $items, $staff, $basicSalary, $grossPay ?? 0, $resolved, $earningResults),
            ];
        }

        $grossContext = $grossPay ?? round($basicSalary + collect($resolved)->sum('amount'), 2);

        foreach ($deferred as $item) {
            $resolved[] = $item + [
                'amount' => self::resolveAmount($item, $items, $staff, $basicSalary, $grossContext, $resolved, $earningResults ?: $resolved),
            ];
        }

        if ($isEarning) {
            $finalGross = round($basicSalary + collect($resolved)->sum('amount'), 2);

            return collect($resolved)
                ->map(function (array $item) use ($items, $staff, $basicSalary, $finalGross): array {
                    if (! in_array($item['calculation_type'], ['percentage_of_gross', 'percentage_of_gross_with_exclusions'], true)) {
                        return $item;
                    }

                    $item['amount'] = self::resolveAmount($item, $items, $staff, $basicSalary, $finalGross, $resolved = [], []);

                    return $item;
                })
                ->values()
                ->all();
        }

        return collect($resolved)->values()->all();
    }

    protected static function resolveAmount(
        array $item,
        Collection $allItems,
        Staff $staff,
        float $basicSalary,
        float $grossPay,
        array $resolvedItems,
        array $earningResults,
        array $stack = [],
    ): float {
        $key = $item['id'] ?: $item['code'];

        if (in_array($key, $stack, true)) {
            return 0;
        }

        $details = $item['calculation_details'] ?? [];
        $stack[] = $key;

        return match ($item['calculation_type']) {
            'fixed', 'fixed_amount' => round((float) ($details['value'] ?? $item['value']), 2),
            'percentage_basic' => round($basicSalary * ((float) $item['value'] / 100), 2),
            'percentage_of_item' => round(self::referenceBaseAmount($details['base_item'] ?? 'basic_salary', $allItems, $staff, $basicSalary, $grossPay, $resolvedItems, $earningResults, $stack) * ((float) ($details['value'] ?? $item['value']) / 100), 2),
            'percentage_gross', 'percentage_of_gross' => round($grossPay * ((float) ($details['value'] ?? $item['value']) / 100), 2),
            'grade_based' => round(self::gradeBasedAmount($details['grade_rules'] ?? [], $staff), 2),
            'salary_structure' => round($basicSalary, 2),
            'percentage_of_gross_with_exclusions' => round(max($grossPay - self::sumExcludedItems($details['excluded_items'] ?? [], $resolvedItems, $earningResults), 0) * ((float) ($details['value'] ?? $item['value']) / 100), 2),
            'sum_of_items' => round(self::sumReferencedItems($details['items_to_sum'] ?? [], $allItems, $staff, $basicSalary, $grossPay, $resolvedItems, $earningResults, $stack), 2),
            'percentage_of_sum' => round(self::sumReferencedItems($details['items_to_sum'] ?? [], $allItems, $staff, $basicSalary, $grossPay, $resolvedItems, $earningResults, $stack) * ((float) ($details['percentage'] ?? $item['value']) / 100), 2),
            'anniversary_based' => round(self::anniversaryAmount($details, $staff, $basicSalary), 2),
            'leave_grant' => self::isAnniversaryMonth($staff, $details) ? round(($basicSalary * 12) * 0.2, 2) : 0.0,
            default => round((float) $item['value'], 2),
        };
    }

    protected static function referenceBaseAmount(
        mixed $reference,
        Collection $allItems,
        Staff $staff,
        float $basicSalary,
        float $grossPay,
        array $resolvedItems,
        array $earningResults,
        array $stack,
    ): float {
        if ($reference === 'basic_salary' || blank($reference)) {
            return $basicSalary;
        }

        $resolvedMatch = collect([...$resolvedItems, ...$earningResults])->first(
            fn (array $item): bool => (string) ($item['id'] ?? '') === (string) $reference || (string) $item['code'] === (string) $reference
        );

        if ($resolvedMatch) {
            return (float) ($resolvedMatch['amount'] ?? 0);
        }

        $item = $allItems->first(
            fn (array $candidate): bool => (string) ($candidate['id'] ?? '') === (string) $reference || (string) $candidate['code'] === (string) $reference
        );

        if (! $item) {
            return 0;
        }

        return self::resolveAmount($item, $allItems, $staff, $basicSalary, $grossPay, $resolvedItems, $earningResults, $stack);
    }

    protected static function sumReferencedItems(
        array $references,
        Collection $allItems,
        Staff $staff,
        float $basicSalary,
        float $grossPay,
        array $resolvedItems,
        array $earningResults,
        array $stack,
    ): float {
        return collect($references)->sum(fn ($reference): float => self::referenceBaseAmount($reference, $allItems, $staff, $basicSalary, $grossPay, $resolvedItems, $earningResults, $stack));
    }

    protected static function sumExcludedItems(array $references, array $resolvedItems, array $earningResults): float
    {
        return collect([...$resolvedItems, ...$earningResults])
            ->filter(fn (array $item): bool => in_array((string) ($item['id'] ?? ''), array_map('strval', $references), true) || in_array((string) $item['code'], array_map('strval', $references), true))
            ->sum('amount');
    }

    protected static function gradeBasedAmount(array $rules, Staff $staff): float
    {
        if ($rules === []) {
            return 0;
        }

        $grade = self::numericGrade($staff->salary_grade_level ?: $staff->salaryTemplate?->grade_level);

        foreach ($rules as $range => $amount) {
            $normalizedRange = strtoupper(trim((string) $range));

            if ($normalizedRange === '') {
                continue;
            }

            if (str_ends_with($normalizedRange, '+')) {
                $min = self::numericGrade(substr($normalizedRange, 0, -1));

                if ($grade >= $min) {
                    return (float) $amount;
                }
            }

            if (str_contains($normalizedRange, '-')) {
                [$min, $max] = array_map(fn ($value): int => self::numericGrade($value), explode('-', $normalizedRange, 2));

                if ($grade >= $min && $grade <= $max) {
                    return (float) $amount;
                }
            }

            if ($grade === self::numericGrade($normalizedRange)) {
                return (float) $amount;
            }
        }

        return 0;
    }

    protected static function anniversaryAmount(array $details, Staff $staff, float $basicSalary): float
    {
        if (! self::isAnniversaryMonth($staff, $details)) {
            return 0;
        }

        return match ($details['amount_method'] ?? 'grade_based') {
            'fixed' => (float) ($details['fixed_amount'] ?? 0),
            'percentage_of_basic' => round($basicSalary * ((float) ($details['percentage_value'] ?? 0) / 100), 2),
            default => self::gradeBasedAmount($details['grade_rules'] ?? [], $staff),
        };
    }

    protected static function isAnniversaryMonth(Staff $staff, array $details): bool
    {
        if (($details['anniversary_only'] ?? true) === false) {
            return true;
        }

        if (! $staff->hire_date) {
            return false;
        }

        return Carbon::parse($staff->hire_date)->month === now()->month;
    }

    protected static function numericGrade(?string $grade): int
    {
        if (blank($grade)) {
            return 0;
        }

        preg_match('/(\d+)/', $grade, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    protected static function payrollItemsFor(Staff $staff, string $type): Collection
    {
        return PayrollItemType::query()
            ->where('school_id', $staff->school_id)
            ->where('type', $type)
            ->where('is_active', true)
            ->where(function ($query) use ($staff): void {
                $query
                    ->when($staff->salary_template_id, fn ($query): mixed => $query->orWhere('salary_template_id', $staff->salary_template_id))
                    ->orWhere(function ($query) use ($staff): void {
                        $query->whereNull('salary_template_id')
                            ->where(function ($query) use ($staff): void {
                                $query->whereNull('grade_level')
                                    ->orWhere('grade_level', $staff->salary_grade_level)
                                    ->orWhere('grade_level', $staff->salaryTemplate?->grade_level);
                            })
                            ->where(function ($query) use ($staff): void {
                                $query->whereNull('step')
                                    ->orWhere('step', $staff->salary_step)
                                    ->orWhere('step', $staff->salaryTemplate?->step);
                            });
                    });
            })
            ->orderBy('name')
            ->get();
    }

    protected static function adjustmentsFor(Staff $staff, string $type): Collection
    {
        return StaffSalaryAdjustment::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
