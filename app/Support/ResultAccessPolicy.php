<?php

namespace App\Support;

use App\Models\ReportCard;
use App\Models\School;
use App\Models\StudentInvoice;

/**
 * Many Nigerian private schools withhold a term's result until that term's
 * fees are cleared. This is opt-in per school (School::withhold_results_for_debtors)
 * so it never surprises a school that doesn't run that policy.
 */
class ResultAccessPolicy
{
    public static function isWithheldForDebt(ReportCard $reportCard): bool
    {
        $school = $reportCard->school;

        if (! $school instanceof School || ! $school->withhold_results_for_debtors) {
            return false;
        }

        return self::outstandingBalance($reportCard) > 0;
    }

    public static function outstandingBalance(ReportCard $reportCard): float
    {
        return (float) StudentInvoice::query()
            ->where('school_id', $reportCard->school_id)
            ->where('student_id', $reportCard->student_id)
            ->where('academic_year_id', $reportCard->academic_year_id)
            ->when($reportCard->term_id, fn ($query) => $query->where('term_id', $reportCard->term_id))
            ->whereNot('status', 'cancelled')
            ->sum('balance');
    }
}
