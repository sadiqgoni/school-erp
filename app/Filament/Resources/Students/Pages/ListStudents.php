<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Support\ClassTabs;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\GuardianStudent;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Support\SchoolStructurePreset;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleStudents')
                ->label('Sample students')
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->modalHeading('Create sample students')
                ->modalDescription('Creates students, guardians, sibling links, and class placements for this school section.')
                ->schema([
                    TextInput::make('count')
                        ->label('Number of students')
                        ->numeric()
                        ->minValue(6)
                        ->maxValue(20)
                        ->default(12)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->action(fn (array $data) => $this->createSampleStudents((int) ($data['count'] ?? 12))),
            CreateAction::make()->label('Add student'),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(Student::class, 'All students', 'enrollments');
    }

    protected function createSampleStudents(int $count): void
    {
        $school = Filament::getTenant();

        if (! $school instanceof School) {
            Notification::make()
                ->danger()
                ->title('No active school')
                ->body('Open a school section before generating students.')
                ->send();

            return;
        }

        $count = max(6, min($count, 20));

        DB::transaction(function () use ($school, $count): void {
            [$academicYear, $term] = $this->ensureCurrentSession($school);
            $classes = $this->ensureClasses($school)->values();
            $sections = $this->ensureSections($school, $classes);
            $families = $this->sampleFamilies();
            $familyCount = count($families);
            $classCount = $classes->count();

            // Each class gets its own cursor into the family list, starting at a
            // different offset (its position among the classes). That way a class
            // cycles through every family before it ever repeats one, instead of
            // the same one or two guardians' children clustering into one class.
            $familyCursorPerClass = [];
            $familyUsageCount = array_fill(0, $familyCount, 0);
            $created = 0;

            foreach (range(1, $count) as $index) {
                $classPosition = ($index - 1) % $classCount;
                $class = $classes[$classPosition];
                $classKey = $class->getKey();

                $slotInClass = $familyCursorPerClass[$classKey] ?? 0;
                $familyCursorPerClass[$classKey] = $slotInClass + 1;
                $familyIndex = ($classPosition + $slotInClass) % $familyCount;

                $familyRound = $familyUsageCount[$familyIndex];
                $familyUsageCount[$familyIndex]++;

                $family = $this->sectionFamily($families[$familyIndex], $school);
                $gender = $index % 3 === 0 ? 'female' : ($index % 2 === 0 ? 'female' : 'male');
                $firstName = $this->firstName($family, $gender, $familyRound);
                $section = $sections[$classKey] ?? null;
                $divisionCode = strtoupper((string) ($school->division ?: 'SCH'));
                $admissionNumber = sprintf('%s/SAMPLE/%s/%03d', $school->code ?: 'SCH', $divisionCode, $index);

                $student = Student::query()->updateOrCreate(
                    [
                        'school_id' => $school->getKey(),
                        'admission_number' => $admissionNumber,
                    ],
                    [
                        'first_name' => $firstName,
                        'middle_name' => $family['middle'] ?? null,
                        'last_name' => $family['surname'],
                        'date_of_birth' => $this->dateOfBirth($school, $class),
                        'gender' => $gender,
                        'blood_group' => ['O+', 'A+', 'B+', 'AB+'][($index - 1) % 4],
                        'religion' => $family['religion'],
                        'phone' => null,
                        'email' => null,
                        'address' => $family['address'],
                        'city' => $family['city'],
                        'state' => $family['state'],
                        'country' => 'Nigeria',
                        'admitted_on' => $academicYear->starts_on,
                        'status' => 'active',
                        'previous_school' => $this->previousSchool($school, $index),
                        'medical_notes' => $index % 7 === 0 ? 'No known allergy. Monitor during outdoor activities.' : null,
                    ],
                );

                $guardian = $this->ensureGuardian($school, $family);
                $this->linkGuardian($school, $student, $guardian, $family);
                $this->enrollStudent($school, $student, $academicYear, $term, $class, $section);

                $created++;
            }

            Notification::make()
                ->success()
                ->title('Sample students ready')
                ->body("Saved {$created} students with guardians and class placements.")
                ->send();
        });
    }

    protected function ensureCurrentSession(School $school): array
    {
        $startYear = now()->month >= 8 ? now()->year : now()->year - 1;
        $endYear = $startYear + 1;

        $academicYear = AcademicYear::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'name' => "{$startYear}/{$endYear}",
            ],
            [
                'starts_on' => "{$startYear}-09-08",
                'ends_on' => "{$endYear}-07-24",
                'is_current' => true,
                'is_active' => true,
            ],
        );

        $term = null;

        foreach ([
            ['name' => 'First Term', 'position' => 1, 'starts_on' => "{$startYear}-09-08", 'ends_on' => "{$startYear}-12-12"],
            ['name' => 'Second Term', 'position' => 2, 'starts_on' => "{$endYear}-01-12", 'ends_on' => "{$endYear}-04-03"],
            ['name' => 'Third Term', 'position' => 3, 'starts_on' => "{$endYear}-04-27", 'ends_on' => "{$endYear}-07-24"],
        ] as $termData) {
            $term = Term::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'academic_year_id' => $academicYear->getKey(),
                    'name' => $termData['name'],
                ],
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

    protected function ensureClasses(School $school)
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
                [
                    'school_id' => $school->getKey(),
                    'code' => $class['code'],
                ],
                [
                    'name' => $class['name'],
                    'level' => $class['level'],
                    'department' => $class['department'] ?: null,
                    'is_active' => true,
                ],
            );
        }

        return SchoolClass::query()
            ->where('school_id', $school->getKey())
            ->orderBy('level')
            ->get();
    }

    protected function ensureSections(School $school, $classes): array
    {
        $sections = [];

        foreach ($classes as $class) {
            $sections[$class->getKey()] = ClassSection::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'school_class_id' => $class->getKey(),
                    'code' => "{$class->code}-A",
                ],
                [
                    'name' => 'A',
                    'capacity' => 35,
                    'is_active' => true,
                ],
            );
        }

        return $sections;
    }

    protected function enrollStudent(School $school, Student $student, AcademicYear $academicYear, Term $term, SchoolClass $class, ?ClassSection $section): void
    {
        Enrollment::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'student_id' => $student->getKey(),
                'academic_year_id' => $academicYear->getKey(),
            ],
            [
                'term_id' => $term->getKey(),
                'school_class_id' => $class->getKey(),
                'class_section_id' => $section?->getKey(),
                'enrolled_on' => $academicYear->starts_on,
                'status' => 'active',
                'remarks' => 'Sample student for client testing.',
            ],
        );
    }

    protected function ensureGuardian(School $school, array $family): Guardian
    {
        return Guardian::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'phone' => $family['phone'],
            ],
            [
                'name' => $family['guardian'],
                'alternate_phone' => $family['alternate_phone'] ?? null,
                'email' => $family['email'],
                'occupation' => $family['occupation'],
                'address' => $family['address'],
                'is_active' => true,
            ],
        );
    }

    protected function linkGuardian(School $school, Student $student, Guardian $guardian, array $family): void
    {
        GuardianStudent::query()->updateOrCreate(
            [
                'school_id' => $school->getKey(),
                'guardian_id' => $guardian->getKey(),
                'student_id' => $student->getKey(),
            ],
            [
                'relationship' => $family['relationship'],
                'is_primary_contact' => true,
                'can_pick_up' => true,
                'receives_sms' => true,
                'notes' => 'Sample family contact .',
            ],
        );
    }

    protected function sampleFamilies(): array
    {
        return [
            [
                'guardian' => 'Malam Ibrahim Abubakar', 'surname' => 'Abubakar', 'middle' => 'Ibrahim', 'relationship' => 'father',
                'phone' => '+2348011100011', 'email' => 'ibrahim.abubakar@example.com', 'occupation' => 'Civil Servant',
                'address' => 'Gwange Ward, Maiduguri', 'city' => 'Maiduguri', 'state' => 'Borno', 'religion' => 'Islam',
                'male' => ['Usman', 'Musa', 'Yusuf'], 'female' => ['Aisha', 'Fatima', 'Maryam'],
                'primary_male' => ['Nasir', 'Mustapha', 'Hamza'], 'primary_female' => ['Jamila', 'Rukayya', 'Asma'],
                'secondary_male' => ['Abdullahi', 'Suleiman', 'Nura'], 'secondary_female' => ['Zulaihat', 'Fadila', 'Sumayya'],
            ],
            [
                'guardian' => 'Hajiya Zainab Bello', 'surname' => 'Bello', 'middle' => 'Sani', 'relationship' => 'mother',
                'phone' => '+2348011100012', 'email' => 'zainab.bello@example.com', 'occupation' => 'Textile Trader',
                'address' => 'Tarauni, Kano', 'city' => 'Kano', 'state' => 'Kano', 'religion' => 'Islam',
                'male' => ['Sani', 'Kabiru', 'Danjuma'], 'female' => ['Zainab', 'Hauwa', 'Khadija'],
                'primary_male' => ['Farouk', 'Mansur', 'Dawud'], 'primary_female' => ['Saadatu', 'Nafisa', 'Lantana'],
                'secondary_male' => ['Iliyasu', 'Mukhtar', 'Tijjani'], 'secondary_female' => ['Aminatu', 'Hafsat', 'Kaltume'],
            ],
            [
                'guardian' => 'Alhaji Musa Lawal', 'surname' => 'Lawal', 'middle' => 'Musa', 'relationship' => 'father',
                'phone' => '+2348011100013', 'email' => 'musa.lawal@example.com', 'occupation' => 'Building Contractor',
                'address' => 'Tudun Wada, Kaduna', 'city' => 'Kaduna', 'state' => 'Kaduna', 'religion' => 'Islam',
                'male' => ['Aminu', 'Haruna', 'Salisu'], 'female' => ['Safiya', 'Rahma', 'Rabi'],
                'primary_male' => ['Jibril', 'Ismail', 'Ridwan'], 'primary_female' => ['Maimuna', 'Farida', 'Sadiya'],
                'secondary_male' => ['Yakubu', 'Sadiq', 'Hashim'], 'secondary_female' => ['Habiba', 'Khadija', 'Aisha'],
            ],
            [
                'guardian' => 'Hajiya Hadiza Garba', 'surname' => 'Garba', 'middle' => 'Aliyu', 'relationship' => 'mother',
                'phone' => '+2348011100014', 'email' => 'hadiza.garba@example.com', 'occupation' => 'Secondary School Teacher',
                'address' => 'Nassarawa GRA, Katsina', 'city' => 'Katsina', 'state' => 'Katsina', 'religion' => 'Islam',
                'male' => ['Aliyu', 'Bashir', 'Umar'], 'female' => ['Hadiza', 'Bilkisu', 'Halima'],
                'primary_male' => ['Auwal', 'Mahmud', 'Nasiru'], 'primary_female' => ['Rashida', 'Falmata', 'Suhaila'],
                'secondary_male' => ['Shehu', 'Balarabe', 'Gambo'], 'secondary_female' => ['Raihana', 'Amina', 'Mubina'],
            ],
            [
                'guardian' => 'Alhaji Sada Isah', 'surname' => 'Isah', 'middle' => 'Sada', 'relationship' => 'father',
                'phone' => '+2348011100015', 'email' => 'sada.isah@example.com', 'occupation' => 'Livestock Trader',
                'address' => 'Runjin Sambo, Sokoto', 'city' => 'Sokoto', 'state' => 'Sokoto', 'religion' => 'Islam',
                'male' => ['Bawa', 'Lawali', 'Sadauki'], 'female' => ['Rakiya', 'Ummi', 'Saudatu'],
                'primary_male' => ['Kabiru', 'Nasiru', 'Sadiq'], 'primary_female' => ['Amina', 'Zulaihat', 'Hadiza'],
                'secondary_male' => ['Aminu', 'Sulaiman', 'Yakubu'], 'secondary_female' => ['Khadija', 'Maryam', 'Fatima'],
            ],
            [
                'guardian' => 'Hajiya Ladi Sulaiman', 'surname' => 'Sulaiman', 'middle' => 'Bala', 'relationship' => 'mother',
                'phone' => '+2348011100016', 'email' => 'ladi.sulaiman@example.com', 'occupation' => 'Grain Merchant',
                'address' => 'Sabon Gari, Gusau', 'city' => 'Gusau', 'state' => 'Zamfara', 'religion' => 'Islam',
                'male' => ['Bala', 'Shehu', 'Isyaku'], 'female' => ['Larai', 'Talatu', 'Jummai'],
                'primary_male' => ['Murtala', 'Tanko', 'Idris'], 'primary_female' => ['Sadiya', 'Balkisu', 'Ramatu'],
                'secondary_male' => ['Nasir', 'Shamsuddeen', 'Abdulkadir'], 'secondary_female' => ['Firdausi', 'Amina', 'Zainab'],
            ],
            [
                'guardian' => 'Mallam Modu Grema', 'surname' => 'Grema', 'middle' => 'Modu', 'relationship' => 'father',
                'phone' => '+2348011100017', 'email' => 'modu.grema@example.com', 'occupation' => 'Fish Trader',
                'address' => 'Old Market Road, Damaturu', 'city' => 'Damaturu', 'state' => 'Yobe', 'religion' => 'Islam',
                'male' => ['Modu', 'Kaka', 'Bukar'], 'female' => ['Yagana', 'Falmata', 'Fannami'],
                'primary_male' => ['Zanna', 'Mustapha', 'Baba'], 'primary_female' => ['Aisha', 'Zara', 'Hauwa'],
                'secondary_male' => ['Goni', 'Ibrahim', 'Umar'], 'secondary_female' => ['Maryam', 'Fatima', 'Zainab'],
            ],
            [
                'guardian' => 'Hajiya Salamatu Yahaya', 'surname' => 'Yahaya', 'middle' => 'Yusuf', 'relationship' => 'mother',
                'phone' => '+2348011100018', 'email' => 'salamatu.yahaya@example.com', 'occupation' => 'Local Government Staff',
                'address' => 'Tunga Low Cost, Minna', 'city' => 'Minna', 'state' => 'Niger', 'religion' => 'Islam',
                'male' => ['Yusuf', 'Ndagi', 'Sadiku'], 'female' => ['Adama', 'Rakiya', 'Halima'],
                'primary_male' => ['Ndanusa', 'Umaru', 'Ibrahim'], 'primary_female' => ['Salamatu', 'Aisha', 'Fatima'],
                'secondary_male' => ['Suleiman', 'Yakubu', 'Musa'], 'secondary_female' => ['Zainab', 'Maryam', 'Hauwa'],
            ],
            [
                'guardian' => 'Mr Davou Gyang', 'surname' => 'Gyang', 'middle' => 'Davou', 'relationship' => 'father',
                'phone' => '+2348011100019', 'email' => 'davou.gyang@example.com', 'occupation' => 'Agricultural Extension Officer',
                'address' => 'Rayfield, Jos', 'city' => 'Jos', 'state' => 'Plateau', 'religion' => 'Christianity',
                'male' => ['Davou', 'Dachung', 'Nanle'], 'female' => ['Comfort', 'Dorcas', 'Mwanret'],
                'primary_male' => ['Bot', 'Danladi', 'Choji'], 'primary_female' => ['Patience', 'Rhoda', 'Nanpon'],
                'secondary_male' => ['Solomon', 'Timothy', 'Yilkes'], 'secondary_female' => ['Deborah', 'Grace', 'Mercy'],
            ],
            [
                'guardian' => 'Mrs Naomi Vandi', 'surname' => 'Vandi', 'middle' => 'Yakubu', 'relationship' => 'mother',
                'phone' => '+2348011100020', 'email' => 'naomi.vandi@example.com', 'occupation' => 'Bank Officer',
                'address' => 'Jimeta, Yola', 'city' => 'Yola', 'state' => 'Adamawa', 'religion' => 'Christianity',
                'male' => ['Yakubu', 'Bulus', 'Filibus'], 'female' => ['Naomi', 'Ruth', 'Suzan'],
                'primary_male' => ['Emmanuel', 'Nathan', 'Daniel'], 'primary_female' => ['Esther', 'Blessing', 'Joy'],
                'secondary_male' => ['Peter', 'James', 'Andrew'], 'secondary_female' => ['Sarah', 'Rebecca', 'Miriam'],
            ],
        ];
    }

    protected function sectionFamily(array $family, School $school): array
    {
        $prefix = match ($school->division) {
            'primary' => 'primary',
            'secondary' => 'secondary',
            default => null,
        };

        if (! $prefix) {
            return $family;
        }

        $family['male'] = $family["{$prefix}_male"] ?? $family['male'];
        $family['female'] = $family["{$prefix}_female"] ?? $family['female'];

        return $family;
    }

    protected function firstName(array $family, string $gender, int $familyRound): string
    {
        $names = $family[$gender] ?? ['Aisha'];

        return $names[$familyRound % count($names)];
    }

    protected function dateOfBirth(School $school, SchoolClass $class): string
    {
        $age = match ($school->division) {
            'nursery' => max(2, min(5, $class->level + 1)),
            'primary' => max(6, min(12, $class->level + 5)),
            'secondary' => max(11, min(17, $class->level + 10)),
            default => max(4, min(16, $class->level + 5)),
        };

        return Carbon::now()
            ->subYears($age)
            ->subMonths($class->level % 6)
            ->startOfMonth()
            ->addDays(($class->level * 3) % 20)
            ->toDateString();
    }

    protected function previousSchool(School $school, int $index): ?string
    {
        return match ($school->division) {
            'nursery' => null,
            'primary' => $index % 4 === 0 ? 'Bright Steps Nursery School' : null,
            'secondary' => $index % 3 === 0 ? 'Unity Primary School' : null,
            default => null,
        };
    }
}
