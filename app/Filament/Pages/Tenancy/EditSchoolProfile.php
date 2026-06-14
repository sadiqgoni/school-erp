<?php

namespace App\Filament\Pages\Tenancy;

use App\Filament\Resources\Schools\Schemas\SchoolForm;
use App\Models\School;
use Filament\Facades\Filament;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditSchoolProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'School Profile';
    }

    public static function canView(Model $tenant): bool
    {
        $user = Filament::auth()->user();

        return (bool) ($user?->is_active && $user->schools()->withoutGlobalScopes()->whereKey($tenant)->exists());
    }

    public function form(Schema $schema): Schema
    {
        return SchoolForm::configure($schema, includeAdminAccount: false, isTenantProfile: true);
    }

    protected function afterSave(): void
    {
        if (! $this->tenant instanceof School || blank($this->tenant->logo_path)) {
            return;
        }

        $parentSchoolId = $this->tenant->parent_school_id ?: $this->tenant->getKey();

        School::query()
            ->withoutGlobalScopes()
            ->whereKey($parentSchoolId)
            ->orWhere('parent_school_id', $parentSchoolId)
            ->update(['logo_path' => $this->tenant->logo_path]);
    }
}
