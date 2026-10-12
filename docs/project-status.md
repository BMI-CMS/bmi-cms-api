# Project Status

Last updated: 2026-10-11.

## Visit Plan

### Done — test environment and sample data (verified)

- Isolated test database `bmi-cms-api_testing` (same local MySQL 8 server). `phpunit.xml` forces it; `tests/TestCase.php` refuses to run tests on any database not ending in `_testing`.
- `config/visit_plan.php` — Visit Plan timezone `Asia/Manila`.
- `AccountSeeder` (60 sample accounts) and `ContactRecordingSeeder` (35 recordings, 1 soft-deleted) cover every rule, overlap, gap and scheduling case. Dates are relative to the run time in Manila time; both seeders are safe to rerun.
- Tests in `tests/Feature/VisitPlan/`:
  - `TestEnvironmentTest` — 6/6 pass (isolation, counts, rerun, Manila midnight / late evening / same-day).
  - `VisitPlanBaselineTest` — expected classification per sample account (see `docs/general-business-rules.md`). Against the **unchanged** current code: 15 pass, 20 fail, 27 incomplete (rules not decided).

### Done — Step 2: account retrieval (verified, awaiting approval for Step 3)

- `DashboardRepository::getAssignedAccounts(int $userId)`: all accounts of the credit counselor, one row per account, latest non-deleted contact recording (`contact_date DESC, id DESC`, MySQL 8 `ROW_NUMBER()`), address and contact details, earlier recordings as `contact_history`. 2 queries. No classification or scheduling filters.
- Not wired to the endpoint yet; `GET /dashboard/assigned-accounts` still uses the old `forVisitedAccount`.
- `VisitPlanAccountRetrievalTest` — 12/12 pass.

### Done — Step 2b: who the Visit Plan returns (eligibility; verified)

Rules: `docs/general-business-rules.md` section 7.1 (confirmed 2026-10-10).

- `DashboardRepository::getVisitPlanAccounts(int $userId, CarbonInterface $now, string $period)`:
  - daily — unvisited accounts with `due_date` in the current Manila month and `<= today`; visited accounts whose latest recording's `next_action_date` (date and time) arrived between `next_action_carry_over_days` ago (floored at the month start) and now.
  - monthly — the same plus unvisited accounts due later in the month and next actions scheduled later in the month. Nothing from another month.
  - Visited = a non-deleted contact recording (not `is_contacted`). Force-prioritized accounts follow the same rules.
- `getDailyAccounts` / `getMonthlyAccounts` use it for `for_visit`; their `visited` windows follow Manila day/month instead of UTC. The old `forVisitedAccount` was removed.
- `config/visit_plan.php`: `next_action_carry_over_days` = 1 (confirmed).
- Endpoints, parameters and response structure are unchanged.
- `VisitPlanEligibilityTest` — 21/21 pass. `VisitPlanAccountRetrievalTest` 15/15, `TestEnvironmentTest` 6/6.
- Index check at volume (about 1,500 accounts per credit counselor, test DB only): both queries use `accounts.assigned_cc_id` and `contact_recordings.account_id` indexes. Daily about 75 ms; monthly worst case (almost every account eligible) about 350 ms. No index added. A composite `accounts(assigned_cc_id, due_date)` is possible if needed (needs approval).
- Timezone (2026-10-11): `config/app.php` is `Asia/Manila`; the Visit Plan has no timezone setting or conversions of its own. `visitPlanWindow` was removed; `getDailyAccounts` / `getMonthlyAccounts` compute their windows with plain Carbon (monthly end is `startOfMonth()->addMonth()`, which also fixes the 31st-of-the-month overflow).
- `contact_recordings.is_contacted` (2026-10-11): new column mirroring `accounts.is_contacted`; migration `2026_10_11_030000_add_is_contacted_to_contact_recordings_table` (verified roll back / re-apply on the test DB, data preserved); model fillable + boolean cast; sample data; returned by the retrieval. **Future feature — its behavior is deliberately not implemented** (rules 7.4).
- `accounts.is_contacted` (2026-10-11): set to `true` on an account's first contact recording (`ContactRecording::booted`, conditional update, never reset). Existing accounts were not backfilled.
- Period queries reviewed (2026-10-11): `getDailyAccounts` / `getMonthlyAccounts` `for_visit` was already correct; the `visited` list (`visitedAccount`) wrongly included soft-deleted recordings — fixed (join now filters `deleted_at`). Tests `AccountFirstContactTest` (7) and the new period-query tests pass.
- **To do on any existing database:** run `php artisan migrate` (adds the column). Timestamps stored before the timezone change are UTC and read 8 hours off — not converted (Open question 19).

### Done — classification of the assigned-accounts endpoint (2026-10-11; awaiting your review)

- `App\Services\SettingService` (renamed from `AccountClassificationService` on 2026-10-11; no old references remain) loads and validates the rules in `account_classification_rules` and classifies the accounts of `GET /api/v1/dashboard/assigned-accounts` (daily and monthly), evaluated by `precedence` (rules: `docs/general-business-rules.md` sections 4-6 and 10). Response property names and structure are unchanged; `DashboardService::getAssignedAccounts` calls it instead of `AllocateAccountsHelper`.
- **Classification is by DPD bucket (2026-10-11):** `DPD 0` = NP0, `DPD 1-30` = DPD 1-30 Days, `DPD 31-60` = NP1, `DPD 61-90` = Priority 1 Accounts, `DPD 91-120` and higher = For Repossession (never for `FIRST DUE DATE` / `NON-STARTER`). NP is not used by any seeded rule, so every account with a known bucket is classified.
- Database: migration `2026_10_11_040000_add_precedence_to_account_classification_rules_table` (adds nullable `precedence`; verified roll back / re-apply with data preserved). `AccountClassificationRule` model fixed (SoftDeletes, array/boolean casts, removed the non-existent `sub_classification`); `AccountClassificationRuleSeeder` rewritten (the old one failed on invalid JSON) and added to `DatabaseSeeder`.
- Invalid or missing rules → HTTP 500 with a clear message and a logged reason (`App\Exceptions\ClassificationRulesException`); no default rules.
- `AllocateAccountsHelper` is untouched (Team and PSGC endpoints still use it).
- Tests: `SettingServiceTest` (every DPD bucket x several NP values, FDD/NS restriction, forced, plans, live rule edits, invalid configuration), `VisitPlanBaselineTest` (every sample account, daily and monthly, response structure, error response, unknown-bucket warning), plus the earlier suites — 393 passed, 8 incomplete (deferred out-of-scope plans), 0 failed in `tests/Feature/VisitPlan`.
- **To run on each existing database:** `php artisan migrate` and `php artisan db:seed --class=AccountClassificationRuleSeeder` (re-run the seeder if it was run before 2026-10-11 evening, so Priority 1 / NP1 / NP0 lose their NP conditions).
- Open: accounts with a missing or unknown `dpd_bucket` are omitted and logged (question 22).

### Done — assigned-accounts-psgc endpoint (2026-10-11; awaiting your review)

- `GET /api/v1/dashboard/assigned-accounts-psgc` was crashing (it iterated the `for_visit` / `visited` array instead of accounts). `DashboardService::assignedAccountsByPSGC` now takes only `user_id` (monthly only; the `period` parameter is removed for this endpoint, via `UserIdRequest`) and uses `DashboardRepository::getVisitPlanAccounts(…, PERIOD_MONTHLY)`, the same accounts as `assigned-accounts?period=monthly`.
- Response: `data` is `{ "<psgc_code> - <city_municipality>": [accounts] }` (key convention unchanged) instead of Prioritization / Regular. Each account has the `assigned-accounts` fields plus `is_force_prioritized`, and `account_classification` is the rules-table label (`SettingService::classifyAccount`). Accounts no rule classifies are kept with `null` and listed in one log warning.
- `assigned_am_id` / `assigned_dh_id` now carry `assigned_am_ar_id` / `assigned_rm__dh_id` in this endpoint (they were always null); `assigned-accounts` still returns null for them. `visit_date` stays null as there, since no such column exists.
- Sample data: `AccountSeeder` spreads accounts over 3 PSGC codes (`137404000` Quezon City, `137403000` Pasig City, `137602000` Makati City — the Makati code is from memory, to be confirmed against the official PSGC list); `CollectionAddressSeeder` and `CustomerDetailSeeder` now fill every account (address in the city of its own PSGC code), idempotently.
- Tests: new `AssignedAccountsPsgcTest` (6 tests); `VisitPlanAccountRetrievalTest` address assertions updated for the new sample data. `tests/Feature/VisitPlan`: 399 passed, 8 incomplete, 0 failed. The full suite also has 12 failures in `AuthTest` / `UserAttestationTest` (`UserFactory` references a missing `App\Models\UserLevel`), not related to this change.
- Not changed: `teamAssignedAccountsPSGC` and the team endpoints; `AllocateAccountsHelper::accountsClassificationPSGC` is now unused by this endpoint.

### Done — team-assigned-accounts endpoint (2026-10-11; awaiting your review)

- `GET /api/v1/dashboard/team-assigned-accounts` (higher-ups) was returning HTTP 500 for ordinary requests: `AllocateAccountsHelper` read a `visit_date` key that no query selects (and `accounts` has no such column). It also used the old hard-coded classification, an arbitrary (not latest) recording per account, `created_at` ranges instead of the section 7.1 scheduling, and trusted any `user_id`.
- It now follows the same rules as `assigned-accounts`: section 7.1 eligibility (daily is a subset of monthly), latest non-deleted recording by `contact_date`/`id`, and rules-table classification (`SettingService::classify`), per credit counselor.
- **Team by requester level (confirmed 2026-10-11):** level 2 = `accounts.assigned_ch_id`, level 3 = `assigned_am_ar_id`, level 4 = `assigned_rm__dh_id`, always limited to the required `psgc_code`.
- **Response:** `data` keeps `repossession_request`, `visit_plan`, `fec_approval`, `collections` (the last three are still the hard-coded placeholders 5, 3, 5 — unchanged, out of scope). Each `visit_plan` entry is `{ credit_counselor_name, total_accounts, accounts: { prioritization, regular, visited } }`. `total_accounts` is the number of accounts in the groups (visited excluded). `visited` follows `assigned-accounts` (daily = today, monthly = this month) for the team's accounts.
- **Account field names changed:** each account now has the `assigned-accounts` fields (`accounts_id`, `assigned_am_id`, `assigned_dh_id`, `activity_records` as a list) instead of the old `account_id`, `user_id`, `assigned_am_ar_id`, `assigned_rm__dh_id` and the single-object `activity_records`. The endpoint was failing before, so no client could have relied on the old shape — confirm against the mobile app.
- **Authorization (security-sensitive, changed on your instruction 2026-10-11):** `TeamVisitPlanRequest` (used only by this route) authorizes exactly like `ForceAssignmentRequest` — the requester's level must be 2, 3 or 4. An earlier version also rejected a `user_id` that was not the authenticated user's; that check was removed, so a level 2-4 user can pass another user's `user_id` (known gap, as in `assigned-accounts`).
- Code: `DashboardRepository::getTeamVisitPlanAccounts` / `getTeamVisitedAccounts` (new, plus private `teamAccountsQuery` / `teamColumn`), `DashboardService::teamAssignedAccounts`, `DashboardController::teamAssignedAccounts`, `App\Http\Requests\TeamVisitPlanRequest`. Shared code used by other routes was not modified: `getDailyTeamAccounts` / `getMonthlyTeamAccounts`, `ContactRecordingQuery::contactRecorded`, `AllocateAccountsHelper`, `TeamAssignedAccountsRequest` (all still used by `team-assigned-accounts-psgc`), and the `assigned-accounts` queries. The 7.1 window logic is duplicated in the team query on purpose.
- Tests: new `TeamAssignedAccountsTest` (14 tests: validation, authorization, structure, per-counselor grouping and psgc filter, same accounts and groups as `assigned-accounts`, daily subset of monthly, `total_accounts`, visited, never-contacted account, levels 3 and 4, other teams excluded). `tests/Feature/VisitPlan`: 413 passed, 8 incomplete, 0 failed. Not tested: pagination/volume (no limit is applied), manual calls from a `.http` file.
- **Pagination (2026-10-11, confirmed):** by credit counselor. Optional `page` (default 1) and `per_page` (default 15, max 50, `Constants::TEAM_ACCOUNTS_PER_PAGE` / `TEAM_ACCOUNTS_MAX_PER_PAGE`), validated in `TeamVisitPlanRequest`. Each `visit_plan` entry still holds all of its counselor's accounts, groups and `visited`, so `total_accounts` stays correct. `data.pagination` = `{ current_page, per_page, total, last_page }` (`total` = counselors with something to show); counselors are ordered by their id. A page beyond the last returns an empty `visit_plan`. Implemented with `DashboardRepository::getTeamCounselorIds` plus an optional counselor filter on the two team queries. A single counselor with about 1,500 accounts is still one large entry.
- **Indexes (2026-10-11, confirmed):** migration `2026_10_12_010000_add_team_psgc_code_indexes_to_accounts_table` adds composite indexes `(assigned_ch_id, psgc_code)`, `(assigned_am_ar_id, psgc_code)`, `(assigned_rm__dh_id, psgc_code)` — one per level, matching the team filter. No index on `psgc_code` existed before (checked with `SHOW INDEX`). Verified on the test DB: apply, roll back (restores the foreign-key indexes) and re-apply; `EXPLAIN` uses the new index for all three levels (60 rows only, so no timing at volume). **To run on each existing database (including the dev DB `bmi-cms-api`, not yet migrated): `php artisan migrate`.**
- Tests now: `TeamAssignedAccountsTest` 19 passed; `tests/Feature/VisitPlan` 418 passed, 8 incomplete, 0 failed.

### Not done (deferred / needs approval)

- Step 3 — classification (which group an eligible account lands in). `VisitPlanBaselineTest`: 16 failing (A06, A07, A08, A10, A13, A22, B05, B08–B13, B15, B17, B19), 27 incomplete. Eligible accounts with fallback, out-of-scope or unlisted next action plans (B08–B15, B25–B28, B30, B31) are still dropped by `AllocateAccountsHelper`.
- Monthly archiving (rule documented in section 7.2; not implemented).
- `team-assigned-accounts-psgc` still uses the old queries and `AllocateAccountsHelper` (it has the same crash and recording bugs; its `total_accounts` counts groups). It is the next candidate and can reuse the new team repository methods.

### Known issues

Current Visit Plan code (`AllocateAccountsHelper`, `DashboardRepository::forVisitedAccount`):

- Compares `no_of_non_payments` as a number; real values are text (`LAST PAID 1 MONTH AGO`), so every regular account becomes `priority_1_accounts`.
- `force_prioritized` overrides `for_repossession`; `fdd_ns` overrides the repossession rule.
- No `due_date` scheduling or month rollover.
- Accounts with fallback next action plans are dropped instead of using `current_category`.
- Latest recording chosen by `MAX(id)` instead of `contact_date`.
- Response key `for_skiptrace` vs spec `skiptrace`.
- `teamAssignedAccountsPSGC` counts groups instead of accounts (`assignedAccountsByPSGC` is fixed, see above).
- `assigned-accounts` accepts any `user_id` from the request (authorization gap; not in scope yet). The same gap exists in `team-assigned-accounts` and `team-assigned-accounts-psgc` (any level 2-4 user can pass another `user_id`).

Dashboard route review (2026-10-12; static code review of `GET /dashboard`, `/assigned-accounts`, `/assigned-accounts-psgc`; nothing executed, nothing fixed yet). Status of each: **open** unless noted. Several of these touch shared code (`DashboardRepository`, `DashboardService`, `Account` joins, the `*Query` classes), so check them before changing related features:

- **Inflated counts/totals in `GET /dashboard` (likely bug, open).** `DashboardRepository::getAccountPerformance` left-joins `ReceiptEncodingQuery`, `RestructuringQuery` and `ForRepossessionQuery` subqueries onto `accounts` with no grouping. An account with several receipts (or restructurings) becomes several rows, so `COUNT(accounts.id)`, `past_due_balance` and the restructured amount are multiplied. `ForRepossessionQuery` also joins `receipt_encodings` again. Fix direction: aggregate each subquery to one row per `account_id` before joining, or use `COUNT(DISTINCT ...)`. Anything else that reuses these `*Query` classes has the same risk. `total_accounts` also only counts accounts whose `created_at` is in the current month; confirm this is intended.
- **Placeholder values in `getSummary` (open).** `fec_accounts` returns `total_accounts` and `collections` returns `total_paid_accounts`. Needs the approved FEC/collections definition.
- **`assigned_am_id` / `assigned_dh_id` always null in `/assigned-accounts` (open, known).** `DashboardService::getAssignedAccounts` reads `$item->assigned_am_id` / `assigned_dh_id`; the query selects `assigned_am_ar_id` / `assigned_rm__dh_id`. `assignedAccountsByPSGC` and `teamAccountEntry` already map them correctly. `visit_date` is null on every endpoint (no such column in `accounts`); drop it or confirm clients accept null.
- **Authorization gap (security-sensitive, open, known).** `user_id` is taken from the request; `UserIdRequest::authorize()` returns `true`. Any authenticated user can read another counselor's accounts (names, balances, addresses, contact details). Needs a decision before release.
- **`/assigned-accounts-psgc` group key (open).** Built from `collecting_addresses.city_municipality`; accounts with no address get keys like `"1234 - "`.
- **Duplicate "could not be classified" warning (open).** `assignedAccountsByPSGC` and `SettingService::classify` log the same message.
- **Unused code (open).** `DashboardRepository::getAssignedAccounts` has no caller on the dashboard; `contact_history` is built in `withContactHistory` (one extra query) but never returned; `Carbon` is imported but unused in `DashboardController`. The account-array mapping is duplicated in `getAssignedAccounts` and `assignedAccountsByPSGC`.
- **Indexes to verify (open).** `ROW_NUMBER() OVER (PARTITION BY account_id ORDER BY contact_date DESC, id DESC)` on `contact_recordings` should have an index on `(account_id, contact_date, id)`; also check `accounts(assigned_cc_id, due_date)`. Run `EXPLAIN` at about 1,500 accounts per user.
- **Tests (open).** No dashboard test covers `GET /dashboard` or `/assigned-accounts`. Add one with an account that has two receipts to catch the inflated-count bug.

Other tests (pre-existing, unrelated to Visit Plan):

- `AuthTest` and `UserAttestationTest` fail (12 tests): they don't send the `BMI_CMS_KEY` header (401), and `UserFactory` uses a missing `App\Models\UserLevel` model.

Open business rules: see `docs/general-business-rules.md` section 9.

## API documentation

- `docs/openapi.yaml` (OpenAPI 3.0.3) documents all 29 routed `/api/v1` endpoints (21 operations; singular/plural aliases of collection and customer routes are documented once). Validated with `@redocly/cli lint` (valid; only `operationId` warnings). Collections, Customers and First Encounter are demo-data endpoints, and `RepossessionController` has no routes.
