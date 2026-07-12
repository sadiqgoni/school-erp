<?php

namespace App\Filament\Pages;

use App\Models\CommunicationLog;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class SchoolHealth extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Health Checks';

    protected static ?string $title = 'School Health';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.school-health';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $healthTab = 'all';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getCurrentPanel()?->getId() === 'admin' && (bool) $user?->isSuperAdmin();
    }

    public function updatedHealthTab(): void
    {
        $this->resetPage();
    }

    public function setHealthTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'needs_attention', 'ready'], true)) {
            return;
        }

        $this->healthTab = $tab;
        $this->resetPage();
    }

    /**
     * @return array<string, array{label: string, count: int}>
     */
    public function healthTabs(): array
    {
        $schools = $this->rootSchoolsForHealth();

        return [
            'all' => [
                'label' => 'All schools',
                'count' => $schools->count(),
            ],
            'needs_attention' => [
                'label' => 'Needs attention',
                'count' => $schools->filter(fn (School $school): bool => self::issueCount($school) > 0)->count(),
            ],
            'ready' => [
                'label' => 'Ready',
                'count' => $schools->filter(fn (School $school): bool => self::issueCount($school) === 0)->count(),
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => School::query()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->whereNull('parent_school_id')
                ->when(
                    $this->healthTab !== 'all',
                    fn (Builder $query): Builder => $query->whereKey($this->schoolIdsForHealthTab($this->healthTab)),
                ))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (School $record): string => $record->code),
                TextColumn::make('sections')
                    ->label('Sections')
                    ->state(fn (School $record): string => self::sectionSummary($record))
                    ->badge()
                    ->color('info'),
                IconColumn::make('has_students')
                    ->label('Has students')
                    ->state(fn (School $record): bool => self::hasStudents($record))
                    ->boolean(),
                IconColumn::make('has_current_term')
                    ->label('Current term set')
                    ->state(fn (School $record): bool => self::hasCurrentTerm($record))
                    ->boolean(),
                IconColumn::make('has_fee_setup')
                    ->label('Fee setup')
                    ->state(fn (School $record): bool => self::hasFeeSetup($record))
                    ->boolean(),
                IconColumn::make('has_admin')
                    ->label('Has admin')
                    ->state(fn (School $record): bool => self::hasAdmin($record))
                    ->boolean(),
                TextColumn::make('failed_communications')
                    ->label('Failed messages')
                    ->state(fn (School $record): int => self::failedCommunicationsCount($record))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('issues')
                    ->label('Open issues')
                    ->state(fn (School $record): int => self::issueCount($record))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'success',
                        $state <= 2 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(false),
            ])
            ->recordActions([
                Action::make('viewHealth')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (School $record): string => $record->name.' health details')
                    ->modalDescription('Each section is checked separately so you can see exactly what needs fixing.')
                    ->modalContent(fn (School $record) => view('filament.pages.school-health-details', [
                        'school' => $record,
                        'sections' => self::healthDetails($record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('No schools yet')
            ->striped();
    }

    protected static function hasFeeSetup(School $school): bool
    {
        return self::healthWorkspaces($school)
            ->contains(fn (School $workspace): bool => self::workspaceHasFeeSetup($workspace));
    }

    protected static function workspaceHasFeeSetup(School $school): bool
    {
        return FeeStructure::query()
            ->where('school_id', $school->getKey())
            ->where('is_active', true)
            ->exists();
    }

    protected static function hasAdmin(School $school): bool
    {
        return self::healthWorkspaces($school)
            ->contains(fn (School $workspace): bool => self::workspaceHasAdmin($workspace));
    }

    protected static function workspaceHasAdmin(School $school): bool
    {
        return User::query()
            ->whereHas('schools', fn (Builder $query) => $query
                ->whereKey($school->getKey())
                ->where('school_user.role', User::SCHOOL_ROLE_ADMIN))
            ->where('is_active', true)
            ->exists();
    }

    protected static function failedCommunicationsCount(School $school): int
    {
        return self::healthWorkspaces($school)
            ->sum(fn (School $workspace): int => self::workspaceFailedCommunicationsCount($workspace));
    }

    protected static function workspaceFailedCommunicationsCount(School $school): int
    {
        return CommunicationLog::query()
            ->where('school_id', $school->getKey())
            ->where('status', 'failed')
            ->count();
    }

    protected static function issueCount(School $school): int
    {
        return self::healthWorkspaces($school)
            ->sum(fn (School $workspace): int => count(self::workspaceIssues($workspace)));
    }

    protected static function hasStudents(School $school): bool
    {
        return self::healthWorkspaces($school)
            ->contains(fn (School $workspace): bool => self::activeStudentsCount($workspace) > 0);
    }

    protected static function hasCurrentTerm(School $school): bool
    {
        return self::healthWorkspaces($school)
            ->contains(fn (School $workspace): bool => self::workspaceHasCurrentTerm($workspace));
    }

    protected static function sectionSummary(School $school): string
    {
        $workspaces = self::healthWorkspaces($school);

        if ($workspaces->count() === 1 && blank($workspaces->first()->division)) {
            return 'Single portal';
        }

        return $workspaces
            ->map(fn (School $workspace): string => $workspace->divisionLabel() ?? $workspace->name)
            ->join(', ');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected static function healthDetails(School $school): Collection
    {
        return self::healthWorkspaces($school)
            ->map(function (School $workspace): array {
                $failedMessages = CommunicationLog::query()
                    ->where('school_id', $workspace->getKey())
                    ->where('status', 'failed')
                    ->latest()
                    ->limit(5)
                    ->get();

                return [
                    'school' => $workspace,
                    'label' => $workspace->divisionLabel() ?? $workspace->name,
                    'active_students_count' => self::activeStudentsCount($workspace),
                    'has_current_term' => self::workspaceHasCurrentTerm($workspace),
                    'has_fee_setup' => self::workspaceHasFeeSetup($workspace),
                    'has_admin' => self::workspaceHasAdmin($workspace),
                    'failed_messages_count' => $failedMessages->count(),
                    'failed_messages' => $failedMessages,
                    'issues' => self::workspaceIssues($workspace),
                ];
            });
    }

    /**
     * @return array<int, string>
     */
    protected static function workspaceIssues(School $school): array
    {
        $issues = [];

        if (self::activeStudentsCount($school) === 0) {
            $issues[] = 'No active students have been added.';
        }

        if (! self::workspaceHasCurrentTerm($school)) {
            $issues[] = 'No current academic year and current term are set.';
        }

        if (! self::workspaceHasFeeSetup($school)) {
            $issues[] = 'No active fee structure is configured.';
        }

        if (! self::workspaceHasAdmin($school)) {
            $issues[] = 'No active school admin is assigned.';
        }

        if (self::workspaceFailedCommunicationsCount($school) > 0) {
            $issues[] = 'Some messages failed to send.';
        }

        return $issues;
    }

    protected static function activeStudentsCount(School $school): int
    {
        return $school->students()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->where('status', 'active')
            ->count();
    }

    protected static function workspaceHasCurrentTerm(School $school): bool
    {
        return $school->academicYears()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->where('is_current', true)
            ->whereHas('terms', fn (Builder $query) => $query->where('is_current', true))
            ->exists();
    }

    /**
     * @return EloquentCollection<int, School>
     */
    protected static function healthWorkspaces(School $school): EloquentCollection
    {
        $divisions = $school->divisions()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->orderByRaw("case division when 'nursery' then 1 when 'primary' then 2 when 'secondary' then 3 else 4 end")
            ->orderBy('name')
            ->get();

        if ($divisions->isNotEmpty()) {
            return $divisions;
        }

        return new EloquentCollection([$school]);
    }

    /**
     * @return EloquentCollection<int, School>
     */
    protected function rootSchoolsForHealth(): EloquentCollection
    {
        return School::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->whereNull('parent_school_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    protected function schoolIdsForHealthTab(string $tab): array
    {
        return $this->rootSchoolsForHealth()
            ->filter(fn (School $school): bool => match ($tab) {
                'needs_attention' => self::issueCount($school) > 0,
                'ready' => self::issueCount($school) === 0,
                default => true,
            })
            ->modelKeys();
    }
}
