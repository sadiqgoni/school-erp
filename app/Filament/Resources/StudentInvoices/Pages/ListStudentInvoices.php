<?php

namespace App\Filament\Resources\StudentInvoices\Pages;

use App\Filament\Resources\StudentInvoices\StudentInvoiceResource;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceItem;
use App\Models\Term;
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
}
