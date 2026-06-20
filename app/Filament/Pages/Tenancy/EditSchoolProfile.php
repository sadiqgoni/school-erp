<?php

namespace App\Filament\Pages\Tenancy;

use App\Filament\Resources\Schools\Schemas\SchoolForm;
use App\Models\School;
use App\Support\SchoolDivisionProvisioner;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
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

        return (bool) ($user?->is_active && (
            $user->isSuperAdmin()
            || $user->schools()->withoutGlobalScopes()->whereKey($tenant)->exists()
        ));
    }

    public function form(Schema $schema): Schema
    {
        return SchoolForm::configure($schema, includeAdminAccount: false, isTenantProfile: true);
    }

    protected function afterSave(): void
    {
        if (! $this->tenant instanceof School) {
            return;
        }

        $parentSchoolId = $this->tenant->parent_school_id ?: $this->tenant->getKey();

        $familyQuery = School::query()
            ->withoutGlobalScopes()
            ->whereKey($parentSchoolId)
            ->orWhere('parent_school_id', $parentSchoolId);

        if (filled($this->tenant->logo_path)) {
            $familyQuery->update(['logo_path' => $this->tenant->logo_path]);
        }

        $sections = collect(data_get($this->data, 'sections', []))
            ->filter()
            ->values()
            ->all();

        if (Filament::auth()->user()?->isSuperAdmin() && $sections !== []) {
            SchoolDivisionProvisioner::syncSections($this->tenant, $sections);

            Notification::make()
                ->success()
                ->title('School sections updated')
                ->body('Nursery, primary, and secondary workspaces have been refreshed for this school.')
                ->send();
        }
    }
}
