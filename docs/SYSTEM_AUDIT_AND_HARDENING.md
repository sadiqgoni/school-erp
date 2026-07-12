# System Audit & Hardening — Handover Notes

**Context:** Full end-to-end review of School Dice ERP as a "walk away for 2 years, must be 99.9% perfect" pass.
Reviews every flow from first-boot registration through each role's daily use.

Status legend: ✅ fixed this pass · 🔧 recommended (not yet done) · 📝 note/by-design

---

## CRITICAL (security / data-integrity — fix before any real client)

### C1. Every parent login was created with the hardcoded password `'password'` ✅ FIXED
- **Was:** `app/Filament/Resources/Guardians/Tables/GuardiansTable.php` `createParentLogin` action hashed the literal string `'password'` for **every** guardian, and told the admin the password was "password".
- **Impact:** Every parent account shared one guessable password. Anyone could log into any parent portal and see another child's fees, results, and **live safety/whereabouts** data. This is the single most serious hole in the system.
- **Fix:** Generate a random temporary password per account, show it to the admin once, and flag the account to force a change at first login (see C3).

### C2. No password-reset flow on either panel ✅ FIXED
- **Was:** Neither `AdminPanelProvider` nor `SchoolPanelProvider` enabled `->passwordReset()`. A teacher/parent/admin who forgot their password was permanently locked out — nobody to call once the developer has left.
- **Fix:** Enabled Filament's password reset on both panels. Requires working SMTP (already configured: Gmail SMTP in `.env`).
- **Follow-up 🔧:** Confirm the production SMTP credentials/app-password are valid and that the `MAIL_FROM_ADDRESS` domain won't be spam-filtered. Consider a dedicated transactional provider (Postmark/SES) for reliability.

### C3. No forced password change on first login ✅ FIXED (staff + parent + school-admin temp accounts)
- Added a `must_change_password` boolean on `users` (migration `2026_07_11_000001`). Temp accounts (school admin via CreateSchool, staff via CreateStaff, parent via GuardiansTable) are created with it set.
- `EnsurePasswordChanged` middleware (both panels' authMiddleware) redirects flagged users to the panel **profile page** (now enabled via `->profile()`) until they change their password. Livewire/logout/profile requests are allowed so the form can submit.
- The flag auto-clears in `User::saving()` when the `password` attribute actually changes.
- Covered by `tests/Feature/ForcePasswordChangeTest.php` (5 tests).

### C4. Note on demo/seed accounts 📝
- `ParentAccountsSeeder` still seeds demo parents with password `password` and does NOT set the force-change flag (so local demo/testing stays convenient). This is intentional for the demo seed only — real accounts created through the UI now get random temp passwords + forced change.

---

## HIGH (should fix — real breakage or exposure risk)

### H1. Hard deletes cascaded across all financial data ✅ FIXED
- **Was:** No model used `SoftDeletes`. Deleting a `School` or `Student` cascade-deleted invoices, payments, ledger entries, report cards — irrecoverable audit/financial history.
- **Fix:** `SoftDeletes` added to `School`, `Student`, `StudentInvoice`, `FeePayment`, `AccountTransaction`, `SalaryPosting` (migration `2026_07_11_000002`).
- **Bug found and fixed while doing this:** ~13 call sites across `User.php`, `School.php`, and five other files used blanket `->withoutGlobalScopes()` to bypass the `BelongsToSchool`/`school-panel-current-tenant` tenant scope (needed so superadmin can browse all schools). That call also silently strips the new `SoftDeletingScope`, which would have let soft-deleted schools reappear in superadmin queries, tenant switchers, and — critically — the NFC device webhook token lookup (a deactivated school's gateway would have kept authenticating forever). All of these were changed to the targeted `->withoutGlobalScope('school-panel-current-tenant')`, which leaves the soft-delete scope intact. Document-number generators (`INV-`/`RCP-`/`TXN-`/`EXP-`/`TRF-`) intentionally still use blanket `withoutGlobalScopes()` on their own model — that's correct there, since a soft-deleted invoice's number must never be reissued.
- Added `TrashedFilter` + `RestoreAction`/`RestoreBulkAction` to the Schools and Students admin tables so deleted records are actually recoverable through the UI, not just in the database.
- Covered by `tests/Feature/SoftDeleteProtectionTest.php` (4 tests, including the device-webhook-stops-working-after-delete case).

### H2. Login rate limiting ✅ VERIFIED (already present, no change needed)
- Filament's `Login` page uses the `WithRateLimiting` trait and calls `$this->rateLimit(5)` before every attempt — 5 attempts before a timed lockout, built in. No gap here.

### H3. Guardian `createParentLogin` uses `firstOrCreate` on email 📝
- If a guardian's email already belongs to a staff user, the parent role attaches to that **staff** login (shared account) rather than erroring. Usually fine (one human, one login) but can surprise. Left as-is; the notification now explicitly tells the admin when this happens ("already had a login — the parent role has been linked").

### H4. Queue worker dependency ✅ VERIFIED — not currently applicable, documented for the future
- Checked: nothing in the codebase implements `ShouldQueue`, and `routes/console.php` has no scheduled commands beyond the default `inspire`. Mail sends synchronously today, so **no cron/queue worker is required right now.**
- Added a section to `docs/CPANEL_DEPLOYMENT_SAFETY.md` explaining exactly what's needed (a `queue:work --stop-when-empty` cron entry) the day a queued job or scheduled command is added, so this isn't rediscovered the hard way in production.

---

## MEDIUM (polish / correctness — lower blast radius)

### M1. `is_active`/subscription gating not enforced on portal access ✅ FIXED (found the middleware was already written but silently inert)
- **Was:** `EnsureSchoolAvailable` middleware already existed with correct logic (blocks deactivated/expired schools, exempts superadmin) but was registered in `authMiddleware()`. Filament resolves `Filament::getTenant()` in its own `IdentifyTenant` middleware, which runs as part of a **separate** `tenantMiddleware()` group that fires *after* `authMiddleware()`. So the check always saw `Filament::getTenant()` as null and silently no-op'd — a deactivated or subscription-expired school's admin could log in and use the portal without any restriction, indefinitely.
- **Fix:** Moved `EnsureSchoolAvailable` to `->tenantMiddleware([...], isPersistent: true)`, where the tenant is actually resolved by the time it runs.
- Covered by `tests/Feature/SchoolAvailabilityTest.php` (4 tests: deactivated blocks, expired blocks, superadmin bypasses, active subscription unaffected).

### M2. Invoice/receipt number race under high concurrency 📝
- Per-school `count()+1` inside `creating`. Two simultaneous invoices in the same school could still collide on the number (unique index will throw, so no silent corruption — just a rare failed request). Acceptable at current scale; revisit with a per-school sequence table if it ever bites.

### M3. No audit trail on sensitive edits 📝 (not addressed this pass)
- `AuditLogger` + `user_activities` exist but coverage of fee edits, post-publish grade changes, role changes, and payment acknowledgements was not verified line-by-line. Worth a dedicated pass if compliance/audit requirements tighten.

### M4. Timezone / date correctness ✅ FIXED
- `config/app.php` timezone was hardcoded `'UTC'`. Changed to `env('APP_TIMEZONE', 'Africa/Lagos')` — WAT is UTC+1, so this affects every `today()`/`now()` call: attendance dates, invoice due dates, movement timestamps, notice expiry. Verified via tinker that `now()` now reports `+01:00`.
- **Found and fixed while verifying this:** `tests/Feature/ExampleTest.php` was a pre-existing broken smoke test — it hit `/` without `RefreshDatabase`, and the welcome page now checks `User::exists()` for first-boot detection, so it always 500'd (confirmed this predates the timezone change by running it in isolation). Added `RefreshDatabase`.

### M5. Parent whereabouts privacy 📝
- Parent whereabouts shows only their own children (scoped) — confirmed correct. Device webhook token is per-school and now correctly stops working the moment a school is soft-deleted (see H1).

---

## LOW / by-design

- L1. Registration is correctly closed after first user (first-boot only). ✅ already correct.
- L2. Paystack webhook signature uses `hash_equals(hash_hmac('sha512', ...))` — timing-safe. ✅ correct.
- L3. Tenant isolation enforced by `BelongsToSchool` global scope on all school-owned models. ✅ (prior pass)
- L4. Results ranking is per-class with joint positions; parents can't fetch unpublished report-card PDFs. ✅ (prior pass)

---

## Flow-by-flow checklist (what was walked)

1. First boot → `/admin/register` (open only when 0 users) → creates superadmin. ✅
2. Login (`SplitLogin`) shared by both panels; inactive users blocked by `EnsureActiveUser`; rate-limited (5 attempts). ✅
3. Superadmin creates a School → provisions division sub-schools, creates school-admin user (forced password change), emails/shows credentials. ✅
4. Deactivated/expired-subscription schools are actually blocked from portal access now (M1). ✅
5. School admin sets up academic year/term/class/section/subject. (spot-checked; no blocking issues found)
6. Students + guardians + staff; staff/guardian login creation — random temp passwords, forced change, soft-deletable. ✅
7. Fees → invoices → Paystack/simulated checkout → webhook settle → receipt. Financial records now soft-deleted, not destroyed. ✅
8. Exams → score entry → compile (per-class ranking) → report cards → publish → parent view/PDF (blocked pre-publish). ✅
9. Teacher portal: assignments, timetable, notices, promotion, score entry. ✅
10. Parent portal: children, invoices, results, homework, timetable, notices, whereabouts (all correctly scoped). ✅
11. Safety/transport: devices, routes, movements, webhook (token per-school, dies with the school). ✅
12. Password reset + forced first-login password change on both panels. ✅
13. Timezone corrected to Africa/Lagos for all date/time logic. ✅
14. Soft-deleted records recoverable through Schools/Students admin tables (TrashedFilter + Restore). ✅

**Test suite: 58/58 passing** (grew from 25 at the start of the original audit to 58 across this and the prior hardening pass).

---

## Remaining recommended work (if resuming)
Nothing critical or high-priority remains open. If continuing:
1. M3 — dedicated audit-trail coverage review (fee edits, post-publish grade changes, role changes).
2. Extend `TrashedFilter`/`RestoreAction` to `FeePayments`, `StudentInvoices`, `AccountTransactions`, `SalaryPostings` tables (currently soft-deletable at the model layer and testable, but only Schools/Students have restore UI — the others rely on direct DB/tinker recovery for now).
3. Confirm production SMTP credentials work end-to-end (welcome mail + password reset) before go-live — this could not be verified from this environment.
4. H3 — consider a distinct warning/confirmation step when linking a parent login to an email that already belongs to a non-parent account.
