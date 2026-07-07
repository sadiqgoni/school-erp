<?php

namespace App\Filament\Pages;

use App\Models\StudentInvoice;
use App\Support\WhatsApp;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeeDebtors extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Fee Debtors';

    protected static ?string $title = 'Fee Debtors';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Payments';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.fee-debtors';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        if (! $user || ! $tenant) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasSchoolRole($tenant, ['admin', 'finance']);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = static::debtorQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => static::debtorQuery()
                ->with(['student.enrollments.schoolClass', 'student.enrollments.classSection', 'school']))
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (StudentInvoice $record): ?string => $record->student?->admission_number),
                TextColumn::make('class')
                    ->label('Class')
                    ->state(fn (StudentInvoice $record): string => self::placementLabel($record))
                    ->badge()
                    ->color('info'),
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->description(fn (StudentInvoice $record): ?string => $record->invoice_date?->format('d M Y')),
                TextColumn::make('total')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('NGN')
                    ->color('success'),
                TextColumn::make('balance')
                    ->label('Owing')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger')
                    ->summarize(Sum::make()
                        ->label('Total owing')
                        ->money('NGN')),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—')
                    ->description(fn (StudentInvoice $record): ?string => self::dueDateDescription($record))
                    ->color(fn (StudentInvoice $record): string => $record->due_date?->isPast() ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Part payment',
                        'overdue' => 'Overdue',
                    ]),
                SelectFilter::make('academic_year_id')
                    ->label('Session')
                    ->relationship('academicYear', 'name'),
                SelectFilter::make('term_id')
                    ->label('Term')
                    ->relationship('term', 'name'),
            ])
            ->recordActions([
                Action::make('whatsappReminder')
                    ->label('Remind on WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (StudentInvoice $record): bool => filled(WhatsApp::invoiceReminderLink($record)))
                    ->url(fn (StudentInvoice $record): string => WhatsApp::invoiceReminderLink($record))
                    ->openUrlInNewTab(),
                Action::make('invoice')
                    ->label('Invoice slip')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (StudentInvoice $record): string => route('student-invoices.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('balance', 'desc')
            ->emptyStateHeading('No debtors 🎉')
            ->emptyStateDescription('Every invoice has been fully paid.')
            ->striped();
    }

    protected static function debtorQuery(): Builder
    {
        return StudentInvoice::query()
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('balance', '>', 0)
            ->whereNot('status', 'cancelled');
    }

    protected static function placementLabel(StudentInvoice $record): string
    {
        $placement = $record->student?->enrollments->sortByDesc('enrolled_on')->first();

        return collect([
            $placement?->schoolClass?->name,
            $placement?->classSection?->name,
        ])->filter()->implode(' ') ?: '—';
    }

    protected static function dueDateDescription(StudentInvoice $record): ?string
    {
        if (! $record->due_date?->isPast()) {
            return null;
        }

        $days = $record->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay());

        return $days.' '.str('day')->plural($days).' overdue';
    }
}
