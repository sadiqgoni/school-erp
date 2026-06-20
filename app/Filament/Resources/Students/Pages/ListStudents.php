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
            $classes = $this->ensureClasses($school);
            $sections = $this->ensureSections($school, $classes);
            $families = $this->sampleFamilies();
            $created = 0;

            foreach (range(1, $count) as $index) {
                $familyIndex = ($index - 1) % count($families);
                $familyRound = intdiv($index - 1, count($families));
                $family = $this->sectionFamily($families[$familyIndex], $school);
                $gender = $index % 3 === 0 ? 'female' : ($index % 2 === 0 ? 'female' : 'male');
                $firstName = $this->firstName($family, $gender, $familyRound);
                $class = $classes[($index - 1) % $classes->count()];
                $section = $sections[$class->getKey()] ?? null;
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
            ['guardian' => 'Malam Ibrahim Abubakar', 'surname' => 'Abubakar', 'middle' => 'Ibrahim', 'relationship' => 'father', 'phone' => '+2348011100011', 'email' => 'ibrahim.abubakar@example.com', 'occupation' => 'Civil Servant', 'address' => 'Gwange Ward, Maiduguri', 'city' => 'Maiduguri', 'state' => 'Borno', 'religion' => 'Islam', 'male' => ['Usman', 'Musa', 'Yusuf'], 'female' => ['Aisha', 'Fatima', 'Maryam'], 'primary_male' => ['Nasir', 'Mustapha', 'Hamza'], 'primary_female' => ['Jamila', 'Rukayya', 'Asma'], 'secondary_male' => ['Abdullahi', 'Suleiman', 'Nura'], 'secondary_female' => ['Zulaihat', 'Fadila', 'Sumayya']],
            ['guardian' => 'Hajiya Zainab Bello', 'surname' => 'Bello', 'middle' => 'Sani', 'relationship' => 'mother', 'phone' => '+2348011100012', 'email' => 'zainab.bello@example.com', 'occupation' => 'Trader', 'address' => 'Tarauni, Kano', 'city' => 'Kano', 'state' => 'Kano', 'religion' => 'Islam', 'male' => ['Sani', 'Kabiru', 'Danjuma'], 'female' => ['Zainab', 'Hauwa', 'Khadija'], 'primary_male' => ['Farouk', 'Mansur', 'Dawud'], 'primary_female' => ['Saadatu', 'Nafisa', 'Lantana'], 'secondary_male' => ['Iliyasu', 'Mukhtar', 'Tijjani'], 'secondary_female' => ['Aminatu', 'Hafsat', 'Kaltume']],
            ['guardian' => 'Alhaji Musa Lawal', 'surname' => 'Lawal', 'middle' => 'Musa', 'relationship' => 'father', 'phone' => '+2348011100013', 'email' => 'musa.lawal@example.com', 'occupation' => 'Business Person', 'address' => 'Tudun Wada, Kaduna', 'city' => 'Kaduna', 'state' => 'Kaduna', 'religion' => 'Islam', 'male' => ['Aminu', 'Haruna', 'Salisu'], 'female' => ['Safiya', 'Rahma', 'Rabi'], 'primary_male' => ['Jibril', 'Ismail', 'Ridwan'], 'primary_female' => ['Maimuna', 'Farida', 'Sadiya'], 'secondary_male' => ['Yakubu', 'Sadiq', 'Hashim'], 'secondary_female' => ['Habiba', 'Khadija', 'Aisha']],
            ['guardian' => 'Mrs Grace Okafor', 'surname' => 'Okafor', 'middle' => 'Chukwuemeka', 'relationship' => 'mother', 'phone' => '+2348011100014', 'email' => 'grace.okafor@example.com', 'occupation' => 'Nurse', 'address' => 'Gwarinpa Estate, Abuja', 'city' => 'Abuja', 'state' => 'FCT', 'religion' => 'Christianity', 'male' => ['Chinedu', 'Emeka', 'Kelechi'], 'female' => ['Adaeze', 'Chinwe', 'Amara'], 'primary_male' => ['Obinna', 'Somto', 'Ikenna'], 'primary_female' => ['Nneka', 'Uchechi', 'Chiamaka'], 'secondary_male' => ['Chibuzor', 'Nonso', 'Ebuka'], 'secondary_female' => ['Ifunanya', 'Ngozi', 'Ogechi']],
            ['guardian' => 'Mr Tunde Adebayo', 'surname' => 'Adebayo', 'middle' => 'Oluwaseun', 'relationship' => 'father', 'phone' => '+2348011100015', 'email' => 'tunde.adebayo@example.com', 'occupation' => 'Banker', 'address' => 'Bodija, Ibadan', 'city' => 'Ibadan', 'state' => 'Oyo', 'religion' => 'Christianity', 'male' => ['Tobi', 'Femi', 'Damilola'], 'female' => ['Simisola', 'Morenike', 'Teniola'], 'primary_male' => ['Ayomide', 'Boluwatife', 'Ireoluwa'], 'primary_female' => ['Toluwani', 'Anjola', 'Olamide'], 'secondary_male' => ['Oluwadamilare', 'Temitayo', 'Akinwale'], 'secondary_female' => ['Yetunde', 'Folashade', 'Moyosore']],
            ['guardian' => 'Hajiya Hadiza Garba', 'surname' => 'Garba', 'middle' => 'Aliyu', 'relationship' => 'mother', 'phone' => '+2348011100016', 'email' => 'hadiza.garba@example.com', 'occupation' => 'Teacher', 'address' => 'Nassarawa GRA, Katsina', 'city' => 'Katsina', 'state' => 'Katsina', 'religion' => 'Islam', 'male' => ['Aliyu', 'Bashir', 'Umar'], 'female' => ['Hadiza', 'Bilkisu', 'Halima'], 'primary_male' => ['Auwal', 'Mahmud', 'Nasiru'], 'primary_female' => ['Rashida', 'Falmata', 'Suhaila'], 'secondary_male' => ['Shehu', 'Balarabe', 'Gambo'], 'secondary_female' => ['Raihana', 'Amina', 'Mubina']],
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
