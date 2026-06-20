<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\LedgerAccount;
use App\Models\PayrollItemType;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentDiscount;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceSampleSetup
{
    public static function createLedgerAccounts(School $school): int
    {
        return DB::transaction(function () use ($school): int {
            $parents = [];
            $count = 0;

            foreach (self::ledgerParents() as $account) {
                $parents[$account['code']] = LedgerAccount::query()->updateOrCreate(
                    ['school_id' => $school->getKey(), 'code' => $account['code']],
                    $account + ['parent_id' => null, 'opening_balance' => 0, 'is_system' => true, 'is_active' => true],
                );
                $count++;
            }

            foreach (self::ledgerChildren($school) as $account) {
                LedgerAccount::query()->updateOrCreate(
                    ['school_id' => $school->getKey(), 'code' => $account['code']],
                    [
                        'parent_id' => $parents[$account['parent_code']]?->getKey() ?? null,
                        'name' => $account['name'],
                        'type' => $account['type'],
                        'opening_balance' => $account['opening_balance'] ?? 0,
                        'is_system' => true,
                        'is_active' => true,
                        'description' => $account['description'],
                    ],
                );
                $count++;
            }

            return $count;
        });
    }

    public static function createBankAccounts(School $school): int
    {
        $name = $school->baseSchoolName();
        $slugNumber = str_pad((string) abs(crc32($school->slug ?: $name)), 10, '0', STR_PAD_LEFT);

        return collect([
            [
                'bank_name' => 'Providus Bank',
                'account_name' => "{$name} School Fees Account",
                'account_number' => substr($slugNumber, 0, 10),
                'branch' => $school->city ?: 'Abuja',
                'is_default' => true,
                'notes' => 'Default collection account for school fees and online payments.',
            ],
            [
                'bank_name' => 'GTBank',
                'account_name' => "{$name} Operations Account",
                'account_number' => substr(strrev($slugNumber), 0, 10),
                'branch' => $school->city ?: 'Abuja',
                'is_default' => false,
                'notes' => 'Operations account for approved school expenses.',
            ],
        ])->map(function (array $account) use ($school): BankAccount {
            return BankAccount::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'account_number' => $account['account_number']],
                $account + ['opening_balance' => 0, 'is_active' => true],
            );
        })->count();
    }

    public static function createFeeTypes(School $school): int
    {
        return collect(self::feeTypes($school))->map(fn (array $feeType) => FeeType::query()->updateOrCreate(
            ['school_id' => $school->getKey(), 'code' => $feeType['code']],
            [
                'name' => $feeType['name'],
                'description' => $feeType['description'],
                'is_required' => $feeType['is_required'],
                'is_active' => true,
            ],
        ))->count();
    }

    public static function createFeeStructures(School $school): int
    {
        return DB::transaction(function () use ($school): int {
            self::createFeeTypes($school);

            [$academicYear, $term] = self::ensureCurrentSession($school);
            $classes = self::ensureClasses($school);
            $feeTypes = FeeType::query()
                ->where('school_id', $school->getKey())
                ->get()
                ->keyBy('code');
            $count = 0;

            foreach ($classes as $class) {
                foreach (self::feeAmountsForClass($school, $class) as $code => $amount) {
                    $feeType = $feeTypes->get($code);

                    if (! $feeType) {
                        continue;
                    }

                    FeeStructure::query()->updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->getKey(),
                            'term_id' => $term->getKey(),
                            'school_class_id' => $class->getKey(),
                            'fee_type_id' => $feeType->getKey(),
                        ],
                        [
                            'school_id' => $school->getKey(),
                            'amount' => $amount,
                            'due_date' => $term->starts_on ? $term->starts_on->copy()->addWeeks(3)->toDateString() : now()->addWeeks(3)->toDateString(),
                            'is_active' => true,
                        ],
                    );
                    $count++;
                }
            }

            return $count;
        });
    }

    public static function createStudentDiscounts(School $school): int
    {
        return DB::transaction(function () use ($school): int {
            [, $term] = self::ensureCurrentSession($school);

            $discounts = [
                [
                    'name' => 'Sibling discount',
                    'type' => 'percentage',
                    'value' => 10,
                    'notes' => 'For families with more than one child in the school.',
                ],
                [
                    'name' => 'Need-based support',
                    'type' => 'fixed',
                    'value' => match ($school->division) {
                        'nursery' => 5000,
                        'primary' => 7500,
                        default => 10000,
                    },
                    'notes' => 'Sample bursary support for finance testing.',
                ],
            ];

            return collect($discounts)->map(fn (array $discount) => StudentDiscount::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'name' => $discount['name'],
                ],
                [
                    'student_id' => null,
                    'school_class_id' => null,
                    'academic_year_id' => null,
                    'term_id' => null,
                    'type' => $discount['type'],
                    'value' => $discount['value'],
                    'starts_on' => $term->starts_on,
                    'ends_on' => $term->ends_on,
                    'is_active' => true,
                    'notes' => $discount['notes'],
                ],
            ))->count();
        });
    }

    public static function createExpenseCategories(School $school): int
    {
        return collect(self::expenseCategories($school))->map(fn (array $category) => ExpenseCategory::query()->updateOrCreate(
            ['school_id' => $school->getKey(), 'code' => $category['code']],
            [
                'name' => $category['name'],
                'description' => $category['description'],
                'is_active' => true,
            ],
        ))->count();
    }

    public static function createExpenses(School $school): int
    {
        return DB::transaction(function () use ($school): int {
            self::createLedgerAccounts($school);
            self::createBankAccounts($school);
            self::createExpenseCategories($school);

            $bankAccount = BankAccount::query()
                ->where('school_id', $school->getKey())
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
            $assetAccount = LedgerAccount::query()
                ->where('school_id', $school->getKey())
                ->where('code', '1010')
                ->first();
            $expenseAccounts = LedgerAccount::query()
                ->where('school_id', $school->getKey())
                ->where('type', 'expense')
                ->get()
                ->keyBy('code');
            $categories = ExpenseCategory::query()
                ->where('school_id', $school->getKey())
                ->get()
                ->keyBy('code');
            $count = 0;

            foreach (self::expenses($school) as $index => $expense) {
                $category = $categories->get($expense['category_code']);
                $expenseAccount = $expenseAccounts->get($expense['ledger_code']);

                if (! $category || ! $expenseAccount) {
                    continue;
                }

                Expense::query()->updateOrCreate(
                    [
                        'school_id' => $school->getKey(),
                        'expense_number' => sprintf('EXP-SAMPLE-%s-%03d', strtoupper((string) ($school->division ?: 'GEN')), $index + 1),
                    ],
                    [
                        'expense_category_id' => $category->getKey(),
                        'expense_date' => now()->subDays(14 - ($index * 3))->toDateString(),
                        'payee' => $expense['payee'],
                        'description' => $expense['description'],
                        'amount' => $expense['amount'],
                        'payment_method' => 'bank_transfer',
                        'bank_account_id' => $bankAccount?->getKey(),
                        'asset_account_id' => $assetAccount?->getKey(),
                        'expense_account_id' => $expenseAccount->getKey(),
                        'reference' => sprintf('SAMPLE-%s-%03d', strtoupper((string) ($school->division ?: 'GEN')), $index + 1),
                        'status' => $index === 0 ? 'paid' : 'approved',
                        'notes' => 'Sample finance expense for client testing.',
                    ],
                );
                $count++;
            }

            return $count;
        });
    }

    public static function createPayrollElements(School $school): int
    {
        return DB::transaction(function () use ($school): int {
            self::createLedgerAccounts($school);

            $accounts = LedgerAccount::query()
                ->where('school_id', $school->getKey())
                ->whereIn('code', ['5020', '5040', '5050', '5060', '2020', '2030'])
                ->get()
                ->keyBy('code');

            $elements = [
                [
                    'type' => PayrollItemType::TYPE_ALLOWANCE,
                    'account_code' => '5040',
                    'name' => 'Housing Allowance',
                    'calculation_type' => 'percentage_of_item',
                    'value' => 15,
                    'calculation_details' => [
                        'base_item' => 'basic_salary',
                        'value' => 15,
                    ],
                ],
                [
                    'type' => PayrollItemType::TYPE_ALLOWANCE,
                    'account_code' => '5050',
                    'name' => 'Transport Allowance',
                    'calculation_type' => 'percentage_of_item',
                    'value' => 8,
                    'calculation_details' => [
                        'base_item' => 'basic_salary',
                        'value' => 8,
                    ],
                ],
                [
                    'type' => PayrollItemType::TYPE_ALLOWANCE,
                    'account_code' => '5060',
                    'name' => 'Meal Subsidy',
                    'calculation_type' => 'percentage_of_item',
                    'value' => 5,
                    'calculation_details' => [
                        'base_item' => 'basic_salary',
                        'value' => 5,
                    ],
                ],
                [
                    'type' => PayrollItemType::TYPE_DEDUCTION,
                    'account_code' => '2020',
                    'name' => 'PAYE Tax',
                    'calculation_type' => 'percentage_of_gross',
                    'value' => 4,
                    'calculation_details' => [
                        'value' => 4,
                    ],
                ],
                [
                    'type' => PayrollItemType::TYPE_DEDUCTION,
                    'account_code' => '2030',
                    'name' => 'Pension Contribution',
                    'calculation_type' => 'percentage_of_item',
                    'value' => 8,
                    'calculation_details' => [
                        'base_item' => 'basic_salary',
                        'value' => 8,
                    ],
                ],
            ];

            foreach ($elements as $element) {
                $account = $accounts->get($element['account_code']);

                if (! $account) {
                    continue;
                }

                PayrollItemType::query()->updateOrCreate(
                    [
                        'school_id' => $school->getKey(),
                        'type' => $element['type'],
                        'code' => $account->code,
                    ],
                    [
                        'ledger_account_id' => $account->getKey(),
                        'name' => $element['name'],
                        'calculation_type' => $element['calculation_type'],
                        'calculation_details' => $element['calculation_details'],
                        'value' => $element['value'],
                        'is_active' => true,
                        'notes' => null,
                        'salary_template_id' => null,
                        'grade_level' => null,
                        'step' => null,
                    ],
                );
            }

            return count($elements);
        });
    }

    public static function createAll(School $school): array
    {
        return DB::transaction(fn (): array => [
            'accounts' => self::createLedgerAccounts($school),
            'banks' => self::createBankAccounts($school),
            'feeTypes' => self::createFeeTypes($school),
            'feeStructures' => self::createFeeStructures($school),
            'discounts' => self::createStudentDiscounts($school),
            'expenseCategories' => self::createExpenseCategories($school),
            'expenses' => self::createExpenses($school),
        ]);
    }

    protected static function ensureCurrentSession(School $school): array
    {
        $startYear = now()->month >= 8 ? now()->year : now()->year - 1;
        $endYear = $startYear + 1;

        $academicYear = AcademicYear::query()->updateOrCreate(
            ['school_id' => $school->getKey(), 'name' => "{$startYear}/{$endYear}"],
            ['starts_on' => "{$startYear}-09-08", 'ends_on' => "{$endYear}-07-24", 'is_current' => true, 'is_active' => true],
        );

        $terms = [
            ['name' => 'First Term', 'position' => 1, 'starts_on' => "{$startYear}-09-08", 'ends_on' => "{$startYear}-12-12"],
            ['name' => 'Second Term', 'position' => 2, 'starts_on' => "{$endYear}-01-12", 'ends_on' => "{$endYear}-04-03"],
            ['name' => 'Third Term', 'position' => 3, 'starts_on' => "{$endYear}-04-27", 'ends_on' => "{$endYear}-07-24"],
        ];

        $term = null;

        foreach ($terms as $termData) {
            $term = Term::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'academic_year_id' => $academicYear->getKey(), 'name' => $termData['name']],
                [
                    'position' => $termData['position'],
                    'starts_on' => $termData['starts_on'],
                    'ends_on' => $termData['ends_on'],
                    'is_current' => $termData['name'] === 'Third Term',
                    'is_active' => true,
                ],
            );
        }

        return [$academicYear, $term];
    }

    protected static function ensureClasses(School $school): Collection
    {
        $classes = SchoolClass::query()
            ->where('school_id', $school->getKey())
            ->orderBy('level')
            ->get();

        if ($classes->isNotEmpty()) {
            return $classes;
        }

        $templates = SchoolStructurePreset::defaultTemplatesForDivision($school->division);
        $templates = $templates === [] ? ['nursery', 'primary', 'secondary'] : $templates;

        foreach (SchoolStructurePreset::defaults($templates) as $class) {
            SchoolClass::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'code' => $class['code']],
                ['name' => $class['name'], 'level' => $class['level'], 'department' => $class['department'] ?: null, 'is_active' => true],
            );
        }

        return SchoolClass::query()
            ->where('school_id', $school->getKey())
            ->orderBy('level')
            ->get();
    }

    protected static function ledgerParents(): array
    {
        return [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'description' => 'Money and resources owned by the school.'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'description' => 'Amounts owed by the school.'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'description' => 'Owner capital and accumulated funds.'],
            ['code' => '4000', 'name' => 'Income', 'type' => 'income', 'description' => 'School revenue accounts.'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'description' => 'School operating cost accounts.'],
        ];
    }

    protected static function ledgerChildren(School $school): array
    {
        $expenseAccounts = match ($school->division) {
            'nursery' => [
                ['code' => '5010', 'name' => 'Teaching Materials Expense', 'description' => 'Toys, worksheets, charts, and early-years learning materials.'],
                ['code' => '5020', 'name' => 'Staff Salaries Expense', 'description' => 'Nursery teaching and support staff salaries.'],
                ['code' => '5030', 'name' => 'Health & Welfare Expense', 'description' => 'First aid, hygiene, and welfare supplies.'],
            ],
            'primary' => [
                ['code' => '5010', 'name' => 'Teaching Materials Expense', 'description' => 'Books, markers, exercise materials, and classroom resources.'],
                ['code' => '5020', 'name' => 'Staff Salaries Expense', 'description' => 'Primary teaching and support staff salaries.'],
                ['code' => '5030', 'name' => 'Utilities & Maintenance Expense', 'description' => 'Power, water, repairs, and school maintenance.'],
            ],
            default => [
                ['code' => '5010', 'name' => 'Teaching Materials Expense', 'description' => 'Textbooks, lesson resources, and classroom materials.'],
                ['code' => '5020', 'name' => 'Staff Salaries Expense', 'description' => 'Secondary teaching and support staff salaries.'],
                ['code' => '5030', 'name' => 'Laboratory & Practical Expense', 'description' => 'Science lab, practical, and technical learning supplies.'],
            ],
        };

        return [
            ['parent_code' => '1000', 'code' => '1010', 'name' => 'Cash and Bank', 'type' => 'asset', 'description' => 'Default cash and bank account for receipts and payments.'],
            ['parent_code' => '1000', 'code' => '1020', 'name' => 'Accounts Receivable', 'type' => 'asset', 'description' => 'Outstanding student fees and receivables.'],
            ['parent_code' => '2000', 'code' => '2010', 'name' => 'Accounts Payable', 'type' => 'liability', 'description' => 'Bills and supplier balances owed.'],
            ['parent_code' => '2000', 'code' => '2020', 'name' => 'PAYE Tax Payable', 'type' => 'liability', 'description' => 'PAYE tax withheld from payroll pending remittance.'],
            ['parent_code' => '2000', 'code' => '2030', 'name' => 'Pension Payable', 'type' => 'liability', 'description' => 'Pension deductions awaiting remittance.'],
            ['parent_code' => '3000', 'code' => '3010', 'name' => 'Proprietor Capital', 'type' => 'equity', 'description' => 'Owner investment and retained school funds.'],
            ['parent_code' => '4000', 'code' => '4010', 'name' => 'School Fee Income', 'type' => 'income', 'description' => 'Tuition and standard student fee revenue.'],
            ['parent_code' => '4000', 'code' => '4020', 'name' => 'Other School Income', 'type' => 'income', 'description' => 'Uniform, transport, hostel, event, and other income.'],
            ...array_map(fn (array $account): array => $account + ['parent_code' => '5000', 'type' => 'expense'], $expenseAccounts),
            ['parent_code' => '5000', 'code' => '5040', 'name' => 'Housing Allowance Expense', 'type' => 'expense', 'description' => 'Housing support paid through payroll.'],
            ['parent_code' => '5000', 'code' => '5050', 'name' => 'Transport Allowance Expense', 'type' => 'expense', 'description' => 'Transport support paid through payroll.'],
            ['parent_code' => '5000', 'code' => '5060', 'name' => 'Meal Subsidy Expense', 'type' => 'expense', 'description' => 'Meal support paid through payroll.'],
            ['parent_code' => '5000', 'code' => '5070', 'name' => 'Leave Grant Expense', 'type' => 'expense', 'description' => 'Leave grant and anniversary payroll support.'],
        ];
    }

    protected static function feeTypes(School $school): array
    {
        $common = [
            ['code' => 'TUITION', 'name' => 'Tuition', 'description' => 'Core termly school fee.', 'is_required' => true],
            ['code' => 'PTA', 'name' => 'PTA Levy', 'description' => 'Parent-teacher association levy.', 'is_required' => true],
            ['code' => 'DEV', 'name' => 'Development Levy', 'description' => 'Facilities and school improvement levy.', 'is_required' => true],
            ['code' => 'EXAM', 'name' => 'Examination Fee', 'description' => 'Term assessment and examination charges.', 'is_required' => true],
            ['code' => 'BOOKS', 'name' => 'Books', 'description' => 'Books and learning materials.', 'is_required' => false],
            ['code' => 'UNIFORM', 'name' => 'Uniform', 'description' => 'School uniform and sportswear.', 'is_required' => false],
            ['code' => 'TRANSPORT', 'name' => 'Transport', 'description' => 'Optional school bus service.', 'is_required' => false],
        ];

        $section = match ($school->division) {
            'nursery' => [
                ['code' => 'WELFARE', 'name' => 'Welfare Pack', 'description' => 'Hygiene, care, and classroom welfare supplies.', 'is_required' => false],
            ],
            'primary' => [
                ['code' => 'ICT', 'name' => 'ICT Fee', 'description' => 'Basic computer studies and technology resources.', 'is_required' => false],
            ],
            default => [
                ['code' => 'LAB', 'name' => 'Laboratory / Practical Fee', 'description' => 'Science laboratory, practical, and technical learning fee.', 'is_required' => true],
                ['code' => 'ICT', 'name' => 'ICT Fee', 'description' => 'Computer studies and digital learning resources.', 'is_required' => false],
            ],
        };

        return [...$common, ...$section];
    }

    protected static function feeAmountsForClass(School $school, SchoolClass $class): array
    {
        $levelStep = max(0, ((int) $class->level) - 1);

        return match ($school->division) {
            'nursery' => [
                'TUITION' => 45000 + ($levelStep * 5000),
                'PTA' => 2500,
                'DEV' => 5000,
                'BOOKS' => 8000,
                'WELFARE' => 5000,
            ],
            'primary' => [
                'TUITION' => 55000 + ($levelStep * 4000),
                'PTA' => 3000,
                'DEV' => 7000,
                'EXAM' => 5000,
                'BOOKS' => 10000,
                'ICT' => 5000,
            ],
            default => [
                'TUITION' => 75000 + ($levelStep * 5000),
                'PTA' => 5000,
                'DEV' => 10000,
                'EXAM' => 10000,
                'BOOKS' => 15000,
                'LAB' => 10000,
                'ICT' => 7500,
            ],
        };
    }

    protected static function expenseCategories(School $school): array
    {
        $common = [
            ['code' => 'SALARIES', 'name' => 'Salaries', 'description' => 'Teaching, admin, and support staff payroll.'],
            ['code' => 'UTILITIES', 'name' => 'Utilities', 'description' => 'Power, water, internet, and waste disposal.'],
            ['code' => 'MAINTENANCE', 'name' => 'Repairs & Maintenance', 'description' => 'Building, furniture, generator, and equipment repairs.'],
            ['code' => 'STATIONERY', 'name' => 'Stationery & Printing', 'description' => 'Office stationery, printing, and photocopying.'],
            ['code' => 'SECURITY', 'name' => 'Security', 'description' => 'Security service and safety support costs.'],
            ['code' => 'TRANSPORT', 'name' => 'Transport & Fuel', 'description' => 'School transport fuel and vehicle running costs.'],
        ];

        $section = match ($school->division) {
            'nursery' => [
                ['code' => 'WELFARE', 'name' => 'Health & Welfare', 'description' => 'First aid, hygiene, and pupil care supplies.'],
            ],
            'primary' => [
                ['code' => 'MATERIALS', 'name' => 'Teaching Materials', 'description' => 'Classroom charts, books, and learning aids.'],
            ],
            default => [
                ['code' => 'LAB', 'name' => 'Laboratory & Practicals', 'description' => 'Laboratory materials and practical supplies.'],
            ],
        };

        return [...$common, ...$section];
    }

    protected static function expenses(School $school): array
    {
        return match ($school->division) {
            'nursery' => [
                ['category_code' => 'WELFARE', 'ledger_code' => '5030', 'payee' => 'Aminu Medical Supplies', 'description' => 'First aid and hygiene supplies', 'amount' => 18500],
                ['category_code' => 'SALARIES', 'ledger_code' => '5020', 'payee' => 'Payroll Batch', 'description' => 'Nursery assistant salary support', 'amount' => 95000],
                ['category_code' => 'STATIONERY', 'ledger_code' => '5010', 'payee' => 'Kaduna Learning Store', 'description' => 'Charts, crayons, and activity sheets', 'amount' => 32000],
            ],
            'primary' => [
                ['category_code' => 'MATERIALS', 'ledger_code' => '5010', 'payee' => 'Arewa Bookshop', 'description' => 'Exercise books and classroom materials', 'amount' => 45000],
                ['category_code' => 'SALARIES', 'ledger_code' => '5020', 'payee' => 'Payroll Batch', 'description' => 'Primary teaching salary support', 'amount' => 130000],
                ['category_code' => 'UTILITIES', 'ledger_code' => '5030', 'payee' => 'AEDC / Utilities', 'description' => 'Power and water bill settlement', 'amount' => 38000],
            ],
            default => [
                ['category_code' => 'LAB', 'ledger_code' => '5030', 'payee' => 'Kano Science Supplies', 'description' => 'Laboratory practical consumables', 'amount' => 68000],
                ['category_code' => 'SALARIES', 'ledger_code' => '5020', 'payee' => 'Payroll Batch', 'description' => 'Secondary teacher salary support', 'amount' => 180000],
                ['category_code' => 'STATIONERY', 'ledger_code' => '5010', 'payee' => 'Prime Stationers', 'description' => 'Exam scripts and printing materials', 'amount' => 52000],
            ],
        };
    }
}
