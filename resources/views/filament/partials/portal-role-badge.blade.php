@php
    $user = filament()->auth()->user();
    $tenant = filament()->getTenant();
    $role = $user?->roleForSchool($tenant);
    $roleLabel = match ($role) {
        \App\Models\User::SCHOOL_ROLE_ADMIN => 'School Admin',
        \App\Models\User::SCHOOL_ROLE_FINANCE => 'Finance',
        \App\Models\User::SCHOOL_ROLE_TEACHER => 'Teacher',
        \App\Models\User::SCHOOL_ROLE_STAFF => 'Staff',
        \App\Models\User::SCHOOL_ROLE_PARENT => 'Parent',
        default => $user?->isSuperAdmin() ? 'Superadmin' : 'User',
    };
@endphp

@if ($tenant && $user)
    <div style="display:flex;align-items:center;gap:.5rem;margin-left:.75rem;min-width:0">
        <span style="display:inline-flex;max-width:18rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;border-left:1px solid rgba(148,163,184,.35);padding-left:.75rem;color:#334155;font-size:.85rem;font-weight:700">
            {{ $tenant->baseSchoolName() }}
            @if ($tenant->divisionLabel())
                <span style="color:#64748b;font-weight:600">&nbsp;· {{ $tenant->divisionLabel() }}</span>
            @endif
        </span>
        <span style="display:inline-flex;align-items:center;border-radius:999px;background:#ecfdf5;color:#047857;padding:.22rem .55rem;font-size:.72rem;font-weight:800">
            {{ $roleLabel }}
        </span>
    </div>
@endif
