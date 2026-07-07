<?php

namespace App\Filament\Resources\ParentAssignments\Tables;

use App\Models\Assignment;
use App\Models\AssignmentConfirmation;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ParentAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::scopeToParent($query))
            ->columns([
                TextColumn::make('title')
                    ->label('Homework')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn (Assignment $record): ?string => $record->subject?->name),
                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->state(fn (Assignment $record): string => $record->classLabel() ?: '—')
                    ->badge()
                    ->color('info'),
                TextColumn::make('assigned_on')
                    ->label('Given')
                    ->date('d M Y'),
                TextColumn::make('due_on')
                    ->label('Due')
                    ->date('d M Y')
                    ->placeholder('No due date')
                    ->weight('semibold')
                    ->color(fn (Assignment $record): string => $record->due_on?->isPast() ? 'danger' : 'success'),
                TextColumn::make('confirmations')
                    ->label('My children')
                    ->state(fn (Assignment $record): string => self::confirmationSummary($record))
                    ->badge()
                    ->color(fn (Assignment $record): string => self::pendingChildren($record)->isEmpty() ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Read')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Assignment $record): string => $record->title)
                    ->modalDescription(fn (Assignment $record): string => implode(' • ', array_filter([
                        $record->classLabel(),
                        $record->subject?->name,
                        $record->due_on ? 'Due '.$record->due_on->format('d M Y') : null,
                    ])))
                    ->modalContent(fn (Assignment $record): HtmlString => new HtmlString(
                        '<div class="prose dark:prose-invert max-w-none whitespace-pre-line">'
                        .e($record->instructions ?: 'No extra instructions — check the title.')
                        .'</div>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('attachment')
                    ->label('Worksheet')
                    ->icon('heroicon-o-paper-clip')
                    ->visible(fn (Assignment $record): bool => filled($record->attachment_path))
                    ->url(fn (Assignment $record): string => Storage::disk('public')->url($record->attachment_path))
                    ->openUrlInNewTab(),
                Action::make('markDone')
                    ->label('My child did it')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Assignment $record): bool => self::pendingChildren($record)->isNotEmpty())
                    ->modalHeading('Confirm homework is done')
                    ->modalDescription('This tells the teacher your child has completed the homework.')
                    ->schema(fn (Assignment $record): array => [
                        Select::make('student_id')
                            ->label('Child')
                            ->options(fn (): array => self::pendingChildren($record)
                                ->mapWithKeys(fn (Student $student): array => [$student->getKey() => $student->full_name])
                                ->all())
                            ->default(fn () => self::pendingChildren($record)->count() === 1
                                ? self::pendingChildren($record)->first()->getKey()
                                : null)
                            ->required(),
                        TextInput::make('note')
                            ->label('Note to teacher (optional)')
                            ->maxLength(255),
                    ])
                    ->action(function (Assignment $record, array $data): void {
                        $guardian = self::guardianForStudent((int) $data['student_id']);

                        AssignmentConfirmation::query()->firstOrCreate(
                            [
                                'assignment_id' => $record->getKey(),
                                'student_id' => $data['student_id'],
                            ],
                            [
                                'school_id' => $record->school_id,
                                'guardian_id' => $guardian?->getKey(),
                                'confirmed_by' => Filament::auth()->id(),
                                'note' => $data['note'] ?? null,
                                'confirmed_at' => now(),
                            ],
                        );

                        Notification::make()
                            ->title('Thank you! The teacher has been notified.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('assigned_on', 'desc')
            ->emptyStateHeading('No homework yet')
            ->emptyStateDescription('Homework given to your children\'s classes will appear here.')
            ->striped();
    }

    public static function scopeToParent(Builder $query): Builder
    {
        $placements = self::childPlacements();

        $query
            ->with(['schoolClass', 'classSection', 'subject', 'confirmations'])
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('status', Assignment::STATUS_PUBLISHED);

        return $query->where(function (Builder $query) use ($placements): void {
            if ($placements->isEmpty()) {
                $query->whereRaw('1 = 0');

                return;
            }

            foreach ($placements as $placement) {
                $query->orWhere(function (Builder $query) use ($placement): void {
                    $query
                        ->where('school_class_id', $placement['class_id'])
                        ->where(fn (Builder $query): Builder => $query
                            ->whereNull('class_section_id')
                            ->when($placement['section_id'], fn (Builder $query, $sectionId) => $query
                                ->orWhere('class_section_id', $sectionId)));
                });
            }
        });
    }

    public static function pendingCountForParent(): int
    {
        return self::scopeToParent(Assignment::query())
            ->get()
            ->filter(fn (Assignment $assignment): bool => self::pendingChildren($assignment)->isNotEmpty())
            ->count();
    }

    /**
     * Children of the logged-in parent in this assignment's class who have not confirmed yet.
     */
    protected static function pendingChildren(Assignment $record): Collection
    {
        $confirmed = $record->confirmations->pluck('student_id');

        return self::children()
            ->filter(function (Student $student) use ($record): bool {
                $placement = self::childPlacements()->firstWhere('student_id', $student->getKey());

                if (! $placement || $placement['class_id'] !== $record->school_class_id) {
                    return false;
                }

                return ! $record->class_section_id || $placement['section_id'] === $record->class_section_id;
            })
            ->reject(fn (Student $student): bool => $confirmed->contains($student->getKey()))
            ->values();
    }

    protected static function confirmationSummary(Assignment $record): string
    {
        $pending = self::pendingChildren($record);

        if ($pending->isEmpty()) {
            return 'Done ✓';
        }

        return 'Waiting: '.$pending->pluck('first_name')->join(', ');
    }

    protected static function children(): Collection
    {
        static $children = null;

        if ($children !== null) {
            return $children;
        }

        return $children = Student::query()
            ->where('school_id', Filament::getTenant()?->getKey())
            ->whereHas('guardianLinks.guardian', fn (Builder $query) => $query
                ->where('user_id', Filament::auth()->id()))
            ->get();
    }

    protected static function childPlacements(): Collection
    {
        static $placements = null;

        if ($placements !== null) {
            return $placements;
        }

        return $placements = self::children()
            ->map(function (Student $student): ?array {
                $enrollment = Enrollment::query()
                    ->where('student_id', $student->getKey())
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                if (! $enrollment) {
                    return null;
                }

                return [
                    'student_id' => $student->getKey(),
                    'class_id' => $enrollment->school_class_id,
                    'section_id' => $enrollment->class_section_id,
                ];
            })
            ->filter()
            ->values();
    }

    protected static function guardianForStudent(int $studentId): ?Guardian
    {
        return Guardian::query()
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('user_id', Filament::auth()->id())
            ->whereHas('studentLinks', fn (Builder $query) => $query->where('student_id', $studentId))
            ->first();
    }
}
