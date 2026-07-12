<?php

namespace App\Support;

use App\Models\ReportCard;
use App\Models\ReportCardTraitRating;
use App\Models\ResultTraitItem;
use App\Models\School;
use Illuminate\Support\Collection;

class ResultTraitSampleSetup
{
    /**
     * @return array<int, array{name: string, category: string, position: int}>
     */
    public static function commonItems(): array
    {
        return [
            ['name' => 'Punctuality', 'category' => 'affective', 'position' => 1],
            ['name' => 'Attendance', 'category' => 'affective', 'position' => 2],
            ['name' => 'Neatness', 'category' => 'affective', 'position' => 3],
            ['name' => 'Politeness', 'category' => 'affective', 'position' => 4],
            ['name' => 'Leadership', 'category' => 'affective', 'position' => 5],
            ['name' => 'Handwriting', 'category' => 'psychomotor', 'position' => 1],
            ['name' => 'Drawing and craft', 'category' => 'psychomotor', 'position' => 2],
            ['name' => 'Sports and games', 'category' => 'psychomotor', 'position' => 3],
            ['name' => 'Verbal fluency', 'category' => 'psychomotor', 'position' => 4],
        ];
    }

    /**
     * @return Collection<int, ResultTraitItem>
     */
    public static function ensureForSchool(School $school): Collection
    {
        return collect(self::commonItems())
            ->map(fn (array $item): ResultTraitItem => ResultTraitItem::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'category' => $item['category'],
                    'name' => $item['name'],
                ],
                [
                    'max_rating' => 5,
                    'position' => $item['position'],
                    'is_active' => true,
                ],
            ));
    }

    public static function rateReportCard(ReportCard $reportCard): void
    {
        $school = $reportCard->school;

        if (! $school instanceof School) {
            return;
        }

        self::ensureForSchool($school)
            ->each(function (ResultTraitItem $item) use ($reportCard): void {
                ReportCardTraitRating::query()->updateOrCreate(
                    [
                        'report_card_id' => $reportCard->getKey(),
                        'result_trait_item_id' => $item->getKey(),
                    ],
                    [
                        'school_id' => $reportCard->school_id,
                        'rating' => self::ratingFor($reportCard, $item),
                        'remarks' => null,
                    ],
                );
            });
    }

    protected static function ratingFor(ReportCard $reportCard, ResultTraitItem $item): int
    {
        $average = (float) ($reportCard->average_score ?? 0);
        $base = match (true) {
            $average >= 80 => 5,
            $average >= 65 => 4,
            $average >= 50 => 3,
            $average >= 40 => 2,
            default => 1,
        };

        $jitter = (crc32($reportCard->student_id.'-'.$item->getKey().'-trait') % 3) - 1;

        return max(1, min((int) $item->max_rating, $base + $jitter));
    }
}
