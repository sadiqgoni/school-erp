<?php

namespace App\Models\Concerns;

use App\Support\CurrentDivision;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Row-level tenancy for every school-owned model.
 *
 * Inside the school panel, all queries are automatically limited to the
 * current division — including raw Model::query() calls in form option
 * closures, header actions, and widgets that Filament's own resource-level
 * tenancy does not cover. Outside the school panel (admin panel, webhooks,
 * PDF routes, tests) nothing changes.
 *
 * The Filament tenant is the parent/client school (one subdomain covers all
 * its divisions); the division actually in scope is a separate, session-held
 * selection (see App\Support\CurrentDivision). No division resolved yet means
 * zero rows here — this must never fall back to "no restriction".
 */
trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school-panel-current-tenant', function (Builder $query): void {
            if (Filament::getCurrentPanel()?->getId() !== 'school') {
                return;
            }

            $query->where($query->getModel()->qualifyColumn('school_id'), CurrentDivision::get()?->getKey());
        });

        static::creating(function (Model $model): void {
            if (filled($model->school_id)) {
                return;
            }

            if (Filament::getCurrentPanel()?->getId() === 'school') {
                $model->school_id = CurrentDivision::get()?->getKey();
            }
        });
    }
}
