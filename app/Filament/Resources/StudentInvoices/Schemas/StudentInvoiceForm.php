<?php

namespace App\Filament\Resources\StudentInvoices\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\LedgerAccount;
use App\Models\Student;
use App\Models\StudentDiscount;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class StudentInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Invoice Details')
                        ->schema([
                            SchoolSelect::make(),
                            Select::make('student_id')
                                ->relationship('student', 'admission_number')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),
                            Select::make('academic_year_id')
                                ->relationship('academicYear', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),
                            Select::make('term_id')
                                ->relationship('term', 'name')
                                ->searchable()
                                ->preload()
                                ->live(),
                            TextInput::make('invoice_number')
                                ->maxLength(60)
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Auto-generated'),
                            Select::make('invoice_type')
                                ->required()
                                ->default('standard')
                                ->options([
                                    'standard' => 'Standard invoice',
                                    'emergency' => 'Emergency / one-off invoice',
                                ]),
                            DatePicker::make('invoice_date')->required()->default(today()),
                            DatePicker::make('due_date'),
                            Select::make('student_discount_id')
                                ->label('Student discount')
                                ->options(fn (): array => StudentDiscount::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload(),
                            TextInput::make('discount')->numeric()->prefix('NGN')->default(0),
                            Select::make('income_account_id')
                                ->label('Income account')
                                ->options(fn (): array => LedgerAccount::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->where('type', 'income')
                                    ->where('is_active', true)
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                    ->all())
                                ->searchable()
                                ->preload(),
                            Select::make('status')->required()->default('unpaid')->options([
                                'unpaid' => 'Unpaid',
                                'partial' => 'Partial',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ]),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Invoice Items')
                        ->schema([
                            Repeater::make('item_entries')
                                ->label('Charges')
                                ->defaultItems(1)
                                ->minItems(1)
                                ->schema([
                                    Select::make('fee_type_id')
                                        ->label('Fee type')
                                        ->options(fn (): array => FeeType::query()->orderBy('name')->pluck('name', 'id')->all())
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                            $set('amount', self::resolveFeeAmount(
                                                $state ? (int) $state : null,
                                                $get('../../student_id'),
                                                $get('../../academic_year_id'),
                                                $get('../../term_id'),
                                            ));
                                        }),
                                    TextInput::make('amount')
                                        ->numeric()
                                        ->prefix('NGN')
                                        ->required()
                                        ->helperText('For standard invoices this can come from fee structure; emergency invoices can be entered manually.')
                                        ->afterStateHydrated(function (Get $get, Set $set): void {
                                            $feeTypeId = $get('fee_type_id');

                                            if ($feeTypeId && blank($get('amount'))) {
                                                $set('amount', self::resolveFeeAmount($feeTypeId, $get('../../student_id'), $get('../../academic_year_id'), $get('../../term_id')));
                                            }
                                        }),
                                ])
                                ->columns(2)
                                ->addActionLabel('Add charge')
                                ->collapsible(),
                        ]),
                    Wizard\Step::make('Notes')
                        ->schema([
                            Textarea::make('notes')->columnSpanFull(),
                        ]),
                ])
                    ->skippable()
                    ->columnSpanFull(),
            ]);
    }

    protected static function resolveFeeAmount(?int $feeTypeId, ?int $studentId, ?int $academicYearId, ?int $termId): ?string
    {
        if (! $feeTypeId || ! $studentId || ! $academicYearId) {
            return null;
        }

        $student = Student::query()->with(['enrollments' => function ($query) use ($academicYearId, $termId): void {
            $query->where('academic_year_id', $academicYearId)
                ->when($termId, fn ($subQuery, $selectedTermId) => $subQuery->where('term_id', $selectedTermId))
                ->latest('enrolled_on');
        }])->find($studentId);

        $schoolClassId = $student?->enrollments->first()?->school_class_id;

        if (! $schoolClassId) {
            return null;
        }

        $feeStructure = FeeStructure::query()
            ->where('academic_year_id', $academicYearId)
            ->where('school_class_id', $schoolClassId)
            ->where('fee_type_id', $feeTypeId)
            ->when($termId, fn ($query, $selectedTermId) => $query->where('term_id', $selectedTermId))
            ->first();

        return $feeStructure?->amount ? number_format((float) $feeStructure->amount, 2, '.', '') : null;
    }
}
