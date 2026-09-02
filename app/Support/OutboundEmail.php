<?php

namespace App\Support;

use App\Mail\CommunicationLogMail;
use App\Models\Assignment;
use App\Models\CommunicationLog;
use App\Models\Guardian;
use App\Models\Notice;
use App\Models\Reminder;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OutboundEmail
{
    public function sendCommunicationLog(CommunicationLog $log): bool
    {
        if ($log->channel !== 'email' || blank($log->recipient_contact)) {
            return false;
        }

        try {
            Mail::to($log->recipient_contact)->send(new CommunicationLogMail($log));

            $log->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
            ])->save();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            $log->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => str($exception->getMessage())->limit(1000)->toString(),
            ])->save();

            return false;
        }
    }

    public function sendReminder(Reminder $reminder): bool
    {
        if ($reminder->channel !== 'email' || blank($reminder->recipient_contact)) {
            return false;
        }

        $log = CommunicationLog::query()->create([
            'school_id' => $reminder->school_id,
            'student_id' => $reminder->student_id,
            'guardian_id' => $reminder->guardian_id,
            'related_type' => $reminder->student_invoice_id ? StudentInvoice::class : null,
            'related_id' => $reminder->student_invoice_id,
            'event_type' => $reminder->type,
            'channel' => 'email',
            'recipient_name' => $reminder->guardian?->name,
            'recipient_contact' => $reminder->recipient_contact,
            'subject' => $this->subjectForReminder($reminder),
            'body' => $reminder->message,
            'metadata' => $reminder->metadata,
        ]);

        if (! $this->sendCommunicationLog($log)) {
            $reminder->forceFill([
                'status' => 'failed',
            ])->save();

            return false;
        }

        $reminder->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
        ])->save();

        return true;
    }

    public function queueNotice(Notice $notice): Collection
    {
        if ($notice->status !== Notice::STATUS_PUBLISHED) {
            return collect();
        }

        return $this->queueForGuardians(
            $this->guardiansForAudience($notice->school_id, $notice->audience_type, $notice->audience_division, $notice->school_class_id, $notice->class_section_id),
            related: $notice,
            eventType: 'notice_published',
            subject: $notice->title,
            body: $notice->body ?: 'A new notice has been published on the parent portal.',
            metadata: [
                'category' => $notice->category,
                'portal_url' => $notice->school?->portalUrl('/parent-notices'),
            ],
        );
    }

    public function queueAssignment(Assignment $assignment): Collection
    {
        if ($assignment->status !== Assignment::STATUS_PUBLISHED) {
            return collect();
        }

        $subject = 'New homework: '.$assignment->title;
        $due = $assignment->due_on?->format('d M Y');
        $body = collect([
            $assignment->school?->name,
            'New homework has been posted for '.$assignment->classLabel().'.',
            $assignment->subject?->name ? 'Subject: '.$assignment->subject->name : null,
            $due ? 'Due: '.$due : null,
            $assignment->instructions,
        ])->filter()->join("\n\n");

        return $this->queueForGuardians(
            $this->guardiansForAudience($assignment->school_id, Notice::AUDIENCE_CLASS, null, $assignment->school_class_id, $assignment->class_section_id),
            related: $assignment,
            eventType: 'assignment_published',
            subject: $subject,
            body: $body,
            metadata: [
                'due_on' => $assignment->due_on?->toDateString(),
                'portal_url' => $assignment->school?->portalUrl('/parent-assignments'),
            ],
        );
    }

    public function queueReportCardPublished(ReportCard $reportCard): Collection
    {
        $reportCard->loadMissing(['school', 'student.guardianLinks.guardian', 'exam', 'term']);

        $studentName = $reportCard->student?->full_name ?? 'your child';
        $body = collect([
            $reportCard->school?->name.': '.$studentName.'\'s report card has been published.',
            $reportCard->exam?->name ? 'Exam: '.$reportCard->exam->name : null,
            $reportCard->term?->name ? 'Term: '.$reportCard->term->name : null,
            'Please sign in to the parent portal to view or download it.',
        ])->filter()->join("\n\n");

        return $this->queueForGuardians(
            $this->guardiansForStudents(collect([$reportCard->student])->filter()),
            related: $reportCard,
            eventType: 'report_card_published',
            subject: 'Report card published',
            body: $body,
            metadata: [
                'portal_url' => $reportCard->school?->portalUrl('/parent-report-cards'),
            ],
        );
    }

    public function queueStudentMovement(StudentMovement $movement): Collection
    {
        $movement->loadMissing(['school', 'student.guardianLinks.guardian', 'busRoute']);

        $studentName = $movement->student?->full_name ?? 'Your child';
        $time = $movement->happened_at?->format('d M Y h:i A') ?? now()->format('d M Y h:i A');
        $route = $movement->busRoute?->name ? "\nRoute: {$movement->busRoute->name}" : '';

        return $this->queueForGuardians(
            $this->guardiansForStudents(collect([$movement->student])->filter()),
            related: $movement,
            eventType: 'student_movement_recorded',
            subject: $studentName.' - '.$movement->eventLabel(),
            body: "{$studentName}: {$movement->eventLabel()} at {$time}.{$route}",
            metadata: [
                'event_type' => $movement->event_type,
                'happened_at' => $movement->happened_at?->toIso8601String(),
                'portal_url' => $movement->school?->portalUrl('/parent-whereabouts'),
            ],
        );
    }

    public function queueForGuardians(Collection $guardians, mixed $related, string $eventType, string $subject, string $body, array $metadata = []): Collection
    {
        return $guardians
            ->filter(fn (Guardian $guardian): bool => (bool) $guardian->is_active && filled($guardian->email))
            ->unique('id')
            ->map(function (Guardian $guardian) use ($related, $eventType, $subject, $body, $metadata): CommunicationLog {
                $log = CommunicationLog::query()->create([
                    'school_id' => $guardian->school_id,
                    'guardian_id' => $guardian->getKey(),
                    'related_type' => is_object($related) ? $related::class : null,
                    'related_id' => is_object($related) && method_exists($related, 'getKey') ? $related->getKey() : null,
                    'event_type' => $eventType,
                    'channel' => 'email',
                    'recipient_name' => $guardian->name,
                    'recipient_contact' => $guardian->email,
                    'subject' => $subject,
                    'body' => $body,
                    'metadata' => $metadata,
                ]);

                $this->sendCommunicationLog($log);

                return $log;
            })
            ->values();
    }

    protected function guardiansForAudience(?int $schoolId, ?string $audienceType, ?string $division, ?int $classId, ?int $sectionId): Collection
    {
        $students = Student::query()
            ->where('school_id', $schoolId)
            ->whereHas('enrollments', function (Builder $query) use ($schoolId, $audienceType, $division, $classId, $sectionId): void {
                $query
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->when($audienceType === Notice::AUDIENCE_DIVISION && filled($division), fn (Builder $query) => $query
                        ->whereHas('schoolClass', fn (Builder $query) => $query->where('department', $division)))
                    ->when($audienceType === Notice::AUDIENCE_CLASS && $classId, fn (Builder $query) => $query
                        ->where('school_class_id', $classId)
                        ->when($sectionId, fn (Builder $query) => $query->where('class_section_id', $sectionId)));
            })
            ->with('guardianLinks.guardian')
            ->get();

        return $this->guardiansForStudents($students);
    }

    protected function guardiansForStudents(Collection $students): Collection
    {
        return $students
            ->flatMap(fn (Student $student): Collection => $student->guardianLinks
                ->map(fn ($link) => $link->guardian)
                ->filter())
            ->values();
    }

    protected function subjectForReminder(Reminder $reminder): string
    {
        return match ($reminder->type) {
            'fee_due' => 'Fee payment reminder',
            default => 'School reminder',
        };
    }
}
