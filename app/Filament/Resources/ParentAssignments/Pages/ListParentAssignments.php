<?php

namespace App\Filament\Resources\ParentAssignments\Pages;

use App\Filament\Resources\ParentAssignments\ParentAssignmentResource;
use Filament\Resources\Pages\ListRecords;

class ListParentAssignments extends ListRecords
{
    protected static string $resource = ParentAssignmentResource::class;

    protected ?string $heading = 'Homework';

    protected ?string $subheading = 'Homework given to your children. Tap "My child did it" once it is completed.';
}
