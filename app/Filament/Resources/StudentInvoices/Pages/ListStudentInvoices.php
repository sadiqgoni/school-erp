<?php

namespace App\Filament\Resources\StudentInvoices\Pages;

use App\Filament\Support\ClassTabs;
use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\LedgerAccount;
use App\Models\SchoolClass;
use App\Models\StudentDiscount;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceItem;
use App\Models\Term;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListStudentInvoices extends ListRecords
{
    protected static string $resource = StudentInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleInvoices')
                ->label('Sample invoices')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample student invoices?')
                ->modalDescription('This uses current students, class placements, fee structures, discount definitions, and the school fee income account.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    FinanceSampleSetup::createLedgerAccounts($tenant);
                    FinanceSampleSetup::createFeeStructures($tenant);
                    FinanceSampleSetup::createStudentDiscounts($tenant);

                    $academicYear = AcademicYear::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('is_current', true)
                        ->first()
                        ?? AcademicYear::query()
                            ->where('school_id', $tenant->getKey())
                            ->latest('starts_on')
                            ->first();
                    $term = $academicYear
                        ? Term::query()
                            ->where('school_id', $tenant->getKey())
                            ->where('academic_year_id', $academicYear->getKey())
                            ->where('is_current', true)
                            ->first()
                        : null;

                    if (! $academicYear || ! $term) {
                        Notification::make()
                            ->warning()
                            ->title('Create a session first')
                            ->body('Create the sample session and terms before generating invoices.')
                            ->send();

                        return;
                    }

                    $incomeAccountId = LedgerAccount::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('code', '4010')
                        ->value('id');
                    $discounts = StudentDiscount::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('is_active', true)
                        ->orderByRaw("case when type = 'percentage' then 0 else 1 end")
                        ->orderBy('id')
                        ->get();
                    $enrollments = Enrollment::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('academic_year_id', $academicYear->getKey())
                        ->where('status', 'active')
                        ->with('student')
                        ->orderBy('student_id')
                        ->get()
                        ->unique('student_id')
                        ->values();

                    if ($enrollments->isEmpty()) {
                        Notification::make()
                            ->warning()
                            ->title('No active students found')
                            ->body('Create or generate students with class placements first.')
                            ->send();

                        return;
                    }

                    $created = 0;

                    DB::transaction(function () use ($tenant, $academicYear, $term, $incomeAccountId, $discounts, $enrollments, &$created): void {
                        foreach ($enrollments as $index => $enrollment) {
                            $structures = FeeStructure::query()
                                ->where('school_id', $tenant->getKey())
                                ->where('academic_year_id', $academicYear->getKey())
                                ->where('term_id', $term->getKey())
                                ->where('school_class_id', $enrollment->school_class_id)
                                ->with('feeType')
                                ->get();

                            if ($structures->isEmpty()) {
                                continue;
                            }

                            $discount = $discounts->isNotEmpty() && $index % 4 === 0 ? $discounts->first() : null;
                            $subtotal = (float) $structures->sum('amount');
                            $discountAmount = $discount?->calculateFor($subtotal) ?? 0;

                            $invoice = StudentInvoice::query()->updateOrCreate(
                                [
                                    'school_id' => $tenant->getKey(),
                                    'student_id' => $enrollment->student_id,
                                    'academic_year_id' => $academicYear->getKey(),
                                    'term_id' => $term->getKey(),
                                    'invoice_type' => 'standard',
                                ],
                                [
                                    'student_discount_id' => $discount?->getKey(),
                                    'income_account_id' => $incomeAccountId,
                                    'invoice_date' => today(),
                                    'due_date' => $term->starts_on?->copy()->addWeeks(3)->toDateString(),
                                    'discount' => $discountAmount,
                                    'status' => 'unpaid',
                                    'notes' => 'Sample invoice generated from fee structures.',
                                    'subtotal' => 0,
                                    'total' => 0,
                                    'amount_paid' => 0,
                                    'balance' => 0,
                                ],
                            );

                            $invoice->items()->delete();

                            foreach ($structures as $structure) {
                                StudentInvoiceItem::query()->create([
                                    'school_id' => $tenant->getKey(),
                                    'student_invoice_id' => $invoice->getKey(),
                                    'fee_type_id' => $structure->fee_type_id,
                                    'description' => $structure->feeType?->name ?? 'Charge',
                                    'amount' => $structure->amount,
                                ]);
                            }

                            $invoice->refreshAmounts();
                            $created++;
                        }
                    });

                    Notification::make()
                        ->success()
                        ->title('Sample invoices ready')
                        ->body("Generated or refreshed {$created} invoice(s) for {$academicYear->name} {$term->name}.")
                        ->send();
                }),
            Action::make('generateClassInvoices')
                ->label('Generate class invoices')
                ->icon('heroicon-o-document-duplicate')
                ->color('primary')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->schema([
                    Select::make('academic_year_id')
                        ->label('Academic year')
                        ->options(fn (): array => AcademicYear::query()
                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                            ->orderByDesc('starts_on')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('term_id')
                        ->label('Term')
                        ->options(fn (): array => Term::query()
                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                            ->orderBy('position')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                    Select::make('school_class_id')
                        ->label('Class')
                        ->options(fn (): array => SchoolClass::query()
                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                            ->orderBy('level')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('invoice_date')
                        ->required()
                        ->default(today()),
                    DatePicker::make('due_date'),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $enrollments = Enrollment::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('academic_year_id', $data['academic_year_id'])
                        ->where('school_class_id', $data['school_class_id'])
                        ->when($data['term_id'] ?? null, fn ($query, $termId) => $query->where('term_id', $termId))
                        ->where('status', 'active')
                        ->get();

                    $structures = FeeStructure::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('academic_year_id', $data['academic_year_id'])
                        ->where('school_class_id', $data['school_class_id'])
                        ->when($data['term_id'] ?? null, fn ($query, $termId) => $query->where('term_id', $termId))
                        ->with('feeType')
                        ->get();

                    if ($structures->isEmpty() || $enrollments->isEmpty()) {
                        Notification::make()
                            ->warning()
                            ->title('Nothing to generate')
                            ->body('Make sure the class has active students and fee structures first.')
                            ->send();

                        return;
                    }

                    $created = 0;

                    DB::transaction(function () use ($data, $enrollments, $structures, $tenant, &$created): void {
                        foreach ($enrollments as $enrollment) {
                            $invoice = StudentInvoice::query()->create([
                                'school_id' => $tenant->getKey(),
                                'student_id' => $enrollment->student_id,
                                'academic_year_id' => $data['academic_year_id'],
                                'term_id' => $data['term_id'] ?: null,
                                'invoice_date' => $data['invoice_date'],
                                'due_date' => $data['due_date'] ?: null,
                                'discount' => 0,
                                'status' => 'unpaid',
                                'notes' => null,
                                'subtotal' => 0,
                                'total' => 0,
                                'amount_paid' => 0,
                                'balance' => 0,
                            ]);

                            foreach ($structures as $structure) {
                                StudentInvoiceItem::query()->create([
                                    'school_id' => $tenant->getKey(),
                                    'student_invoice_id' => $invoice->getKey(),
                                    'fee_type_id' => $structure->fee_type_id,
                                    'description' => $structure->feeType?->name ?? 'Charge',
                                    'amount' => $structure->amount,
                                ]);
                            }

                            $invoice->refreshAmounts();
                            $created++;
                        }
                    });

                    Notification::make()
                        ->success()
                        ->title('Invoices generated')
                        ->body("Generated {$created} student invoice(s) from class fee structures.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(StudentInvoice::class, 'All invoices');
    }
}
