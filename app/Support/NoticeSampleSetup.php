<?php

namespace App\Support;

use App\Models\Notice;
use App\Models\School;
use Illuminate\Support\Collection;

/**
 * A realistic, ready-to-edit notice board so a newly onboarded school's
 * parent portal and notice board aren't empty on first login.
 */
class NoticeSampleSetup
{
    public static function createForSchool(School $school, ?int $createdBy = null): int
    {
        return collect(self::notices())
            ->map(function (array $notice) use ($school, $createdBy) {
                $daysAgo = $notice['days_ago'] ?? 0;
                unset($notice['days_ago']);

                return Notice::query()->updateOrCreate(
                    [
                        'school_id' => $school->getKey(),
                        'title' => $notice['title'],
                    ],
                    $notice + [
                        'status' => Notice::STATUS_PUBLISHED,
                        'published_at' => now()->subDays($daysAgo),
                        'created_by' => $createdBy,
                    ],
                );
            })
            ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>|array<int, array<string, mixed>>
     */
    protected static function notices(): array
    {
        return [
            [
                'title' => 'Resumption Date for New Session',
                'body' => "All pupils and students are to resume for the new academic session as scheduled. School uniforms should be complete, and all outstanding fees from the previous session should be cleared before resumption.\n\nParents are reminded to update any change of contact phone number with the school office.",
                'category' => 'general',
                'audience_type' => Notice::AUDIENCE_ALL,
                'is_pinned' => true,
                'days_ago' => 12,
            ],
            [
                'title' => 'Parent-Teacher Association (PTA) Meeting',
                'body' => "The PTA general meeting for this term will hold at the school hall. All parents and guardians are strongly encouraged to attend, as termly academic performance and welfare matters will be discussed.\n\nRefreshments will be served after the meeting.",
                'category' => 'event',
                'audience_type' => Notice::AUDIENCE_ALL,
                'is_pinned' => false,
                'days_ago' => 8,
            ],
            [
                'title' => 'Second Instalment of School Fees Now Due',
                'body' => "This is a reminder that the second instalment of this term's school fees is now due. Parents are encouraged to make payment promptly to avoid disruption to their ward's studies.\n\nPlease contact the bursary for any payment plan enquiries.",
                'category' => 'fees',
                'audience_type' => Notice::AUDIENCE_ALL,
                'is_pinned' => true,
                'days_ago' => 5,
            ],
            [
                'title' => 'Sallah Break — School Closure Notice',
                'body' => "The school will be closed for the Sallah celebration and will resume normal academic activities immediately after the break, as communicated earlier in the term calendar.\n\nThe management wishes all staff, parents, and pupils a peaceful and joyful celebration with their families.",
                'category' => 'general',
                'audience_type' => Notice::AUDIENCE_ALL,
                'is_pinned' => false,
                'days_ago' => 20,
            ],
            [
                'title' => 'Inter-House Sports and Founder\'s Day',
                'body' => "The school's Inter-House Sports competition and Founder's Day celebration will hold this term. Pupils should come with their house colours, and parents/guardians are cordially invited to grace the occasion and cheer their wards.",
                'category' => 'event',
                'audience_type' => Notice::AUDIENCE_ALL,
                'is_pinned' => false,
                'days_ago' => 3,
            ],
            [
                'title' => 'Early Closure Due to Weather Advisory',
                'body' => "Due to the heavy downpour forecast for this afternoon, the school will close earlier than the usual time today. Parents and guardians are kindly requested to make arrangements to pick up their children promptly.\n\nStay safe.",
                'category' => 'urgent',
                'audience_type' => Notice::AUDIENCE_ALL,
                'is_pinned' => true,
                'days_ago' => 1,
                'expires_on' => now()->addDay()->toDateString(),
            ],
        ];
    }
}
