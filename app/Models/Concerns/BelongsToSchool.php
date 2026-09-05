<?php

namespace App\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Row-level tenancy for every school-owned model.
 *
 * Inside the school panel, all queries are automatically limited to the
 * current tenant school — including raw Model::query() calls in form
 * option closures, header actions, and widgets that Filament's own
 * resource-level tenancy does not cover. Outside the school panel
 * (admin panel, webhooks, PDF routes, tests) nothing changes.
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school-panel-current-tenant', function (Builder $query): void {
            $tenant = Filament::getTenant();

            if (Filament::getCurrentPanel()?->getId() !== 'school' || ! $tenant) {
                return;
            }

            $query->where($query->getModel()->qualifyColumn('school_id'), $tenant->getKey());
        });

        static::creating(function (Model $model): void {
            if (filled($model->school_id)) {
                return;
            }

            $tenant = Filament::getTenant();

            if (Filament::getCurrentPanel()?->getId() === 'school' && $tenant) {
                $model->school_id = $tenant->getKey();
            }
        });
    }
}
