<?php

namespace App\Console\Commands;

use App\Models\CommunicationLog;
use App\Models\Reminder;
use App\Support\OutboundEmail;
use Illuminate\Console\Command;

class SendQueuedEmails extends Command
{
    protected $signature = 'communications:send-emails {--limit=100}';

    protected $description = 'Send queued email communication logs and due email reminders.';

    public function handle(OutboundEmail $outboundEmail): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $sent = 0;
        $failed = 0;

        CommunicationLog::query()
            ->with('school')
            ->where('channel', 'email')
            ->where('status', 'queued')
            ->oldest()
            ->limit($limit)
            ->get()
            ->each(function (CommunicationLog $log) use ($outboundEmail, &$sent, &$failed): void {
                $outboundEmail->sendCommunicationLog($log) ? $sent++ : $failed++;
            });

        Reminder::query()
            ->with(['school', 'guardian'])
            ->where('channel', 'email')
            ->where('status', 'pending')
            ->where(fn ($query) => $query->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()))
            ->oldest('scheduled_for')
            ->limit($limit)
            ->get()
            ->each(function (Reminder $reminder) use ($outboundEmail, &$sent, &$failed): void {
                $outboundEmail->sendReminder($reminder) ? $sent++ : $failed++;
            });

        $this->info("Email notifications sent: {$sent}; failed: {$failed}.");

        return self::SUCCESS;
    }
}
