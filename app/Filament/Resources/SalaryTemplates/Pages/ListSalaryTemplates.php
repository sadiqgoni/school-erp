<?php

namespace App\Filament\Resources\SalaryTemplates\Pages;

use App\Filament\Resources\SalaryTemplates\SalaryTemplateResource;
use App\Models\SalaryTemplate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSalaryTemplates extends ListRecords
{
    protected static string $resource = SalaryTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadSample')
                ->label('Download sample')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $handle = fopen('php://output', 'w');
                    fputcsv($handle, self::csvHeaders());

                    foreach (self::sampleRows() as $row) {
                        fputcsv($handle, $row);
                    }

                    fclose($handle);
                }, 'salary-scale-contiss-sample.csv', ['Content-Type' => 'text/csv'])),
            Action::make('importCsv')
                ->label('Upload CSV')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('success')
                ->modalHeading('Upload salary scale CSV')
                ->schema([
                    FileUpload::make('file')
                        ->label('CSV file')
                        ->disk('local')
                        ->directory('imports/salary-scales')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant || blank($data['file'] ?? null)) {
                        return;
                    }

                    $path = Storage::disk('local')->path($data['file']);
                    $csv = Reader::createFromPath($path);
                    $csv->setHeaderOffset(0);

                    $created = 0;
                    $updated = 0;
                    $skipped = 0;

                    foreach ($csv->getRecords() as $record) {
                        $templates = self::expandRecordToTemplates($record);

                        if ($templates->isEmpty()) {
                            $skipped++;

                            continue;
                        }

                        foreach ($templates as $templateData) {
                            $existing = SalaryTemplate::query()
                                ->where('school_id', $tenant->getKey())
                                ->where('code', $templateData['code'])
                                ->first();

                            SalaryTemplate::query()->updateOrCreate(
                                ['school_id' => $tenant->getKey(), 'code' => $templateData['code']],
                                Arr::except($templateData, ['code']) + [
                                    'housing_allowance' => 0,
                                    'transport_allowance' => 0,
                                    'meal_allowance' => 0,
                                    'other_allowance' => 0,
                                    'pension_deduction' => 0,
                                    'tax_deduction' => 0,
                                    'other_deduction' => 0,
                                    'is_active' => true,
                                ],
                            );

                            $existing ? $updated++ : $created++;
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Salary scale imported')
                        ->body("Created {$created}, updated {$updated}, skipped {$skipped}.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    protected static function csvHeaders(): array
    {
        return [
            'grade_level',
            'step1',
            'step2',
            'step3',
            'step4',
            'step5',
            'step6',
            'step7',
            'step8',
            'step9',
            'step10',
        ];
    }

    protected static function sampleRows(): array
    {
        return [
            ['01', 77500.00, 78370.25, 79240.42, 80110.67, 80980.83, 81851.08, 82721.25, 83591.50, 84461.67, 85331.92],
            ['02', 77922.83, 78992.33, 80061.75, 81131.25, 82200.75, 83270.25, 84339.67, 85409.17, 86478.67, 87548.17],
            ['03', 79415.75, 80700.42, 81985.08, 83269.83, 84554.50, 85839.17, 87123.83, 88408.58, 89693.25, 90977.92],
            ['04', 83796.75, 85289.67, 86782.58, 88275.50, 89768.42, 91261.33, 92754.25, 94247.17, 95740.08, 97233.00],
            ['05', 91975.50, 93794.83, 95614.00, 97433.33, 99252.58, 101071.92, 102891.17, 104710.42, 106529.67, 108349.00],
            ['06', 130994.83, 134335.50, 137676.17, 141016.75, 144357.42, 147698.00, 151038.67, 154379.25, 157719.92, 161060.50],
            ['07', 170985.42, 175441.50, 179897.58, 184353.67, 188809.67, 193265.75, 197721.83, 202177.92, 206634.00, 211090.08],
            ['08', 192087.33, 197331.92, 202576.50, 207821.33, 213065.92, 218310.75, 223555.33, 228800.17, 234044.75, 239289.58],
        ];
    }

    protected static function expandRecordToTemplates(array $record): Collection
    {
        $hasMatrixSteps = collect(array_keys($record))
            ->contains(fn (string $key): bool => str($key)->lower()->startsWith('step'));

        if ($hasMatrixSteps) {
            $gradeLevel = trim((string) ($record['grade_level'] ?? $record['grade_level_from'] ?? ''));

            if ($gradeLevel === '' || str($gradeLevel)->lower()->contains('registrar')) {
                return collect();
            }

            return collect($record)
                ->filter(fn (mixed $value, string $key): bool => str($key)->lower()->startsWith('step'))
                ->map(function (mixed $value, string $key) use ($gradeLevel): ?array {
                    $monthlyBasic = self::moneyValue(['amount' => $value], 'amount');

                    if ($monthlyBasic <= 0) {
                        return null;
                    }

                    $stepNumber = (int) str($key)->after('step')->toString();
                    $step = str_pad((string) $stepNumber, 2, '0', STR_PAD_LEFT);
                    $normalizedGrade = str_pad((string) preg_replace('/\D+/', '', $gradeLevel), 2, '0', STR_PAD_LEFT);

                    return [
                        'code' => "GL{$normalizedGrade}-S{$step}",
                        'name' => "Grade Level {$normalizedGrade} Step {$step}",
                        'grade_level' => "GL {$normalizedGrade}",
                        'step' => $step,
                        'monthly_basic' => $monthlyBasic,
                        'annual_basic' => round($monthlyBasic * 12, 2),
                        'notes' => 'Imported from CONTISS-style step matrix.',
                    ];
                })
                ->filter()
                ->values();
        }

        $code = trim((string) ($record['code'] ?? ''));
        $name = trim((string) ($record['name'] ?? ''));

        if ($code === '' || $name === '') {
            return collect();
        }

        return collect([[
            'code' => $code,
            'name' => $name,
            'grade_level' => self::stringValue($record, 'grade_level'),
            'step' => self::stringValue($record, 'step'),
            'monthly_basic' => self::moneyValue($record, 'monthly_basic'),
            'annual_basic' => self::moneyValue($record, 'annual_basic'),
            'notes' => self::stringValue($record, 'notes'),
        ]]);
    }

    protected static function stringValue(array $record, string $key): ?string
    {
        $value = trim((string) ($record[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    protected static function moneyValue(array $record, string $key): float
    {
        return (float) str_replace([',', '₦', 'NGN', ' '], '', (string) ($record[$key] ?? 0));
    }
}
