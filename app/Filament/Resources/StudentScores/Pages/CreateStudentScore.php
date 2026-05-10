<?php

namespace App\Filament\Resources\StudentScores\Pages;

use App\Filament\Resources\StudentScores\StudentScoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentScore extends CreateRecord
{
    protected static string $resource = StudentScoreResource::class;
}
