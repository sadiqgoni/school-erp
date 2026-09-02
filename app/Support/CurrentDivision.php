<?php

namespace App\Support;

use App\Models\Guardian;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Resolves which division of the current (parent-school) Filament tenant a
 * user is working in for this request.
 *
 * Division is no longer implied by the subdomain (one subdomain now covers a
 * whole client school, all its divisions included), so it's tracked in the
 * session instead, namespaced per parent-tenant id so a value set while
 * working under one client school can never be misread as the active
 * division after switching to a different client school's subdomain.
 *
 * get() re-validates the stored id against the user's currently-accessible
 * divisions on every call rather than trusting the session blindly: if an
 * admin revokes a user's access to a division mid-session, the very next
 * request must stop resolving to it, not just the next login.
 */
class CurrentDivision
{
    /**
     * The workspace-resolution queries below must compute ground truth
     * independent of any School/BelongsToSchool scoping. They deliberately
     * avoid Eloquent relation-existence queries (whereHas/whereDoesntHave)
     * on School — those build fresh nested query instances that pick
     * School's own global scope back up regardless of withoutGlobalScope()
     * on the outer builder, which either recurses back into this class
     * (guarded below) or silently narrows results in ways that are hard to
     * spot. Plain pivot-table lookups sidestep the whole class of problem.
     */
    protected static bool $resolving = false;

    public static function sessionKeyFor(School $parentTenant): string
    {
        return "current_division_id.{$parentTenant->getKey()}";
    }

    /**
     * Divisions the user can access, restricted to the given parent
     * school's own row and its children.
     *
     * @return Collection<int, School>
     */
    public static function availableFor(User $user, School $parentTenant): Collection
    {
        if (static::$resolving) {
            return collect();
        }

        static::$resolving = true;

        try {
            $candidateIds = School::query()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->where(function ($query) use ($parentTenant): void {
                    $query
                        ->whereKey($parentTenant->getKey())
                        ->orWhere('parent_school_id', $parentTenant->getKey());
                })
                ->pluck('id');

            return static::workspacesAccessibleTo($user, $candidateIds);
        } finally {
            static::$resolving = false;
        }
    }

    /**
     * Every division the user can access across every client school —
     * the basis for the top-level Filament tenant switcher, which maps
     * each of these up to its parent school (see User::getTenants()).
     *
     * @return Collection<int, School>
     */
    public static function allAccessibleWorkspaces(User $user): Collection
    {
        if (static::$resolving) {
            return collect();
        }

        static::$resolving = true;

        try {
            $candidateIds = School::query()->withoutGlobalScope('school-panel-current-tenant')->pluck('id');

            return static::workspacesAccessibleTo($user, $candidateIds);
        } finally {
            static::$resolving = false;
        }
    }

    /**
     * @param  Collection<int, int>  $candidateIds
     * @return Collection<int, School>
     */
    protected static function workspacesAccessibleTo(User $user, Collection $candidateIds): Collection
    {
        $childParentIds = School::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->whereIn('parent_school_id', $candidateIds)
            ->pluck('parent_school_id')
            ->unique();

        // portalWorkspaces(), reimplemented on plain ids: a division
        // (division column set) or a legacy school with no children.
        $workspaceIds = School::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->whereIn('id', $candidateIds)
            ->where(function ($query) use ($childParentIds): void {
                $query
                    ->whereNotNull('division')
                    ->orWhereNotIn('id', $childParentIds->all() ?: [0]);
            })
            ->pluck('id');

        if (! $user->isSuperAdmin()) {
            $staffAccessibleIds = DB::table('school_user')
                ->where('user_id', $user->getKey())
                ->where('role', '!=', User::SCHOOL_ROLE_PARENT)
                ->whereIn('school_id', $workspaceIds)
                ->pluck('school_id');

            $guardianAccessibleIds = Guardian::query()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->where('user_id', $user->getKey())
                ->where('is_active', true)
                ->whereIn('school_id', $workspaceIds)
                ->get()
                ->filter(fn (Guardian $guardian) => DB::table('guardian_students')
                    ->where('guardian_id', $guardian->getKey())
                    ->join('students', 'students.id', '=', 'guardian_students.student_id')
                    ->whereNull('students.deleted_at')
                    ->exists())
                ->pluck('school_id');

            $workspaceIds = $staffAccessibleIds->merge($guardianAccessibleIds)->unique();
        }

        return School::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->whereIn('id', $workspaceIds)
            ->orderByRaw("case division when 'nursery' then 1 when 'primary' then 2 when 'secondary' then 3 else 4 end")
            ->orderBy('id')
            ->get();
    }

    public static function get(): ?School
    {
        $parentTenant = Filament::getTenant();

        if (! $parentTenant instanceof School) {
            return null;
        }

        $id = session(static::sessionKeyFor($parentTenant));

        if (! $id) {
            return null;
        }

        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return static::availableFor($user, $parentTenant)->firstWhere('id', $id);
    }

    public static function set(School $division, School $parentTenant): void
    {
        session([static::sessionKeyFor($parentTenant) => $division->getKey()]);
    }

    public static function clear(School $parentTenant): void
    {
        session()->forget(static::sessionKeyFor($parentTenant));
    }
}
