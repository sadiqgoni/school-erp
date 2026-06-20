<?php

namespace App\Models\Concerns;

trait CalculatesSalaryItemAmount
{
    public function amountFor(float $basicSalary, float $grossPay = 0): float
    {
        if (in_array($this->calculation_type, ['percentage_basic', 'percentage_of_item'], true)) {
            return round($basicSalary * ((float) $this->value / 100), 2);
        }

        if (in_array($this->calculation_type, ['percentage_gross', 'percentage_of_gross', 'percentage_of_gross_with_exclusions'], true)) {
            return round($grossPay * ((float) $this->value / 100), 2);
        }

        return round((float) $this->value, 2);
    }

    public static function calculationOptions(): array
    {
        return [
            'fixed' => 'Fixed amount',
            'fixed_amount' => 'Fixed Amount',
            'percentage_basic' => '% of basic salary',
            'percentage_of_item' => 'Percentage of Item',
            'percentage_gross' => '% of gross pay',
            'percentage_of_gross' => 'Percentage of Gross',
            'grade_based' => 'Grade-Based',
            'salary_structure' => 'Salary Structure',
            'percentage_of_gross_with_exclusions' => 'Percentage with Exclusions',
            'sum_of_items' => 'Sum of Items',
            'percentage_of_sum' => 'Percentage of Sum',
            'anniversary_based' => 'Anniversary Month-Based',
            'leave_grant' => 'Leave Grant (20% Annual)',
        ];
    }
}
