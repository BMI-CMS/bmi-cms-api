# General Business Rules — Visit Plan

Reference for the confirmed Visit Plan business rules. Each rule is marked:

- **Confirmed** — stated or approved by the project owner.
- **Open** — not decided yet. Do not implement a guess; ask first.

Sources: `docs/visit_plan_business_rules_claude.md` (primary feature spec) and the owner's clarifications of 2026-10-10. If this file and the spec disagree, ask the owner.

Last updated: 2026-10-14 (section 10: maintenance guide for the classification rules and the planned back-office sync decisions).

---

## 1. General conventions (Confirmed)

- API response keys use `snake_case`.
- **Timezone (confirmed 2026-10-11):** the application timezone is **`Asia/Manila`** (`config/app.php`, `timezone`). It is the only timezone setting: Carbon (`now()`, `today()`, `startOfMonth()`, …), PHP and stored timestamps all follow it, and "today", "this month", "due" and "same day" mean Manila time. Do not pass a timezone to Carbon calls, call `setTimezone()` or add/subtract hours, and do not add another timezone setting.
- Stored `DATETIME` values (`contact_date`, `next_action_date`, `created_at`, …) are Manila local time. **Existing rows written before 2026-10-11 were stored as UTC** and read 8 hours earlier than they were; this was not converted (see Open question 19). The MySQL session timezone is not set by the application, so database-side `NOW()` is not used for business dates.
- `accounts.due_date` is a `DATE` and is read as a Manila calendar date. `contact_recordings.next_action_date` and `contact_date` are `DATETIME` and carry the time of day.
- Classification and scheduling rules must be parameterized and kept in a rules store separate from the main application database. The connection, structure, caching and failure behavior still need approval before implementation (see Open questions).
- Every account in a Visit Plan result has exactly one final classification.

## 2. Source values from LUCAS (Confirmed)

The values below come from the LUCAS developers' integration response. Store and compare them exactly as received — do not normalize spelling, case or singular/plural.

### 2.1 `dpd_bucket`

`DPD 0`, `DPD 1-30`, `DPD 31-60`, `DPD 61-90`, `DPD 91-120`, `DPD 121-150`, `DPD 151-180`, `DPD 181-210`, `DPD 211-240`, `DPD 241-270`, `DPD 271-300`, `DPD 301-330`, `DPD 331-360`, `DPD MORE THAN 360`

### 2.2 `payment_status`

`FIRST DUE DATE`, `NON-STARTER`, `WITH PAYMENT`

### 2.3 `no_of_non_payments`

Stored as text (`accounts.no_of_non_payments` is `VARCHAR(100)`). There is no maximum.

| Stored value | Meaning |
|---|---|
| `UPDATED` | NP0 |
| `LAST PAID 1 MONTH AGO` | NP1 |
| `LAST PAID 2 MONTH AGO` | NP2 (note: singular "MONTH") |
| `LAST PAID 3 MONTHS AGO` | NP3 |
| `LAST PAID 4 MONTHS AGO` | NP4 |
| `LAST PAID 5 MONTHS AGO` | NP5 |
| … same pattern | NPn |

- The database keeps the string value as received. The code converts it to NPn (a number) before applying any rule (confirmed 2026-10-10). Never compare the strings as numbers directly.
- The NP value alone never decides a classification. Always combine it with `dpd_bucket` and the precedence rules.
- NP4 and higher get no extra classification of their own. They count toward the "NP ≥ n" rules below.

## 3. `current_category` labels (Confirmed)

Labels saved on a contact recording to preserve the account's base category:

`DPD 1-30 Days`, `NP0`, `NP1`, `Priority 1 Accounts`, `FDD/NS`, `For Repossession`

Which exact text the mobile app saves cannot be verified yet: contact recording save is still a mock (`CollectionService::recordAction`). Do not change the mobile contract.

## 4. Classifications (rules live in `account_classification_rules`, see section 10)

Labels are the specification's exact labels. Confirmed 2026-10-11 unless marked.

### 4.1 Prioritization

| Label (response property) | Rule | Status |
|---|---|---|
| `For Repossession` (`for_repossession`) | `dpd_bucket` is `DPD 91-120` or any higher bucket (`DPD 121-150` … `DPD 331-360`, `DPD MORE THAN 360`) **and** `payment_status` is neither `FIRST DUE DATE` nor `NON-STARTER`. NP is not part of the rule. | Confirmed 2026-10-11 (replaces "NP ≥ 3 and DPD 91-120", see Open question 21) |
| `For Repossession` via an approved `FOR REPO` next action plan | Needs the higher-up approval contract | Open — not implemented; an unapproved `FOR REPO` plan uses the base classification |
| `Priority 1 Accounts` (`priority_1_accounts`) | `dpd_bucket = DPD 61-90`. NP is not used (2026-10-11; it used to be NP ≥ 2 **and** `DPD 61-90`) | Confirmed 2026-10-11 |
| `FDD/NS` (`fdd_ns`) | `payment_status` is `FIRST DUE DATE` or `NON-STARTER`; ignore NP and `dpd_bucket` | Confirmed |
| `Promise To Pay` (`promise_to_pay`) | `next_action_plan = PROMISE TO PAY` | Confirmed |
| `Skiptrace` (`for_skiptrace`) | `next_action_plan = FOR SKIPTRACE` | Confirmed (response property name kept as `for_skiptrace`) |
| `Notice & Demand Letter` (`notice_and_demand_letter`) | `next_action_plan = FOR SERVICE OF COLLECTION NOTICE` (the old `…/ATTY'S LETTER` value is no longer used) | Confirmed |
| `Forced Prioritized` (`force_prioritized`) | `accounts.is_force_prioritized = true` (the flag, not a plan) | Confirmed |

`Promise To Pay`, `Skiptrace`, `Notice & Demand Letter` and `Forced Prioritized` belong to the Next Action Plan group of the response where they already were (`prioritization.next_action_plan.*`, except Promise To Pay).

**`FIRST DUE DATE` / `NON-STARTER` accounts can never be For Repossession**, whatever their NP or bucket. This restriction beats the rule priority, but they can still be `Forced Prioritized` (and any other classification they qualify for).

### 4.2 Regular

| Label (response property) | Rule | Status |
|---|---|---|
| `DPD 1-30 Days` (`dpd_1_30_days`) | `dpd_bucket = DPD 1-30` (bucket only, never a day count) | Confirmed |
| `NP1` (`np_1`) | `dpd_bucket = DPD 31-60`. NP is not used (2026-10-11; it used to be NP = 1 **and** `DPD 31-60`) | Confirmed 2026-10-11 |
| `NP0` (`np_0`) | `dpd_bucket = DPD 0` (good payer). Supersedes "NP0 in any bucket": NP is not used | Confirmed 2026-10-11 |
| `no_contact_recording` | Discontinued. Never return it or use it as a fallback | Confirmed |

DPD buckets (confirmed 2026-10-11). **The DPD bucket is the primary reference and decides the classification on its own; NP is not an additional condition for any bucket.**

| DPD bucket | Meaning | Classification |
|---|---|---|
| `DPD 0` | 0 days past due, good payer (corresponds to NP0) | `NP0` |
| `DPD 1-30` | 1-30 days past due | `DPD 1-30 Days` |
| `DPD 31-60` | more than 1 month, less than 2 months (corresponds to NP1) | `NP1` |
| `DPD 61-90` | more than 2 months, less than 3 months (corresponds to NP2) | `Priority 1 Accounts` |
| `DPD 91-120` | more than 3 months, less than 4 months | `For Repossession` |
| `DPD 121-150` … `DPD 331-360`, `DPD MORE THAN 360` | the same progression by each bucket's day range | `For Repossession` |

`FIRST DUE DATE` / `NON-STARTER` accounts are `FDD/NS` and are never `For Repossession`; they can still be `Forced Prioritized`. A missing or unknown `dpd_bucket` cannot be classified (see Open question 22).

## 5. Precedence (Confirmed)

Highest first; the first matching rule decides:

1. `For Repossession` (with its FDD/NS restriction) — not overridable by force prioritization.
2. `Forced Prioritized`.
3. The next action plan of the account's latest recording: `Promise To Pay`, `Skiptrace`, `Notice & Demand Letter`, or the `current_category` fallback (section 6). In `period=monthly` an account whose next action is later this month (not yet due) is grouped by that plan as well; in `daily` only due plans reach this step.
4. Base classification, in this order: `FDD/NS` > `Priority 1 Accounts` > `DPD 1-30 Days` > `NP1` > `NP0`.

A due next action plan beats the base `FDD/NS` (spec section 6, "for now"). Plans without a dedicated group (`FOR REVISIT/CALL`, `FOR COLLECTION`, an unapproved `FOR REPO`) use the **base classification**. Out-of-scope plans (Section 8) and recordings without a next action date are a later feature: for now an out-of-scope plan also falls through to the base classification.

## 6. Fallback to `current_category` (Confirmed)

When the latest recording's next action plan is one of these, the account's classification is its recording's `current_category`:

`FOR FIELD VISIT`, `FOR FILING BRGY BLOTTER/COMPLAIN`, `TO ATTEND BARANGAY HEARING`, `FOR DISCREPANCY ADJUSTMENT`, `FOR ASSUMPTION OF UNIT`, `FOR WRITE OFF (MINIMAL BALANCE)`

- Applies only when no higher-precedence classification applies.
- The recording's category wins even if the account's current attributes would now give a different base classification — except that a recorded `For Repossession` never applies to an FDD/NS account (the exclusions of a classification's rule always hold).
- If `current_category` is null, empty or not one of the classification labels, the **base classification** is used (every account must have a classification). It is never replaced by an arbitrary default.
- The response carries the category in `activity_records[].current_category`; the classifier reads it from the latest record there.
- Spelling note: the master list seeder has `FOR WRITE OFF(MINIMAL BALANCE)` (no space). The spec spelling above is used for matching.

## 7. Scheduling (Confirmed unless marked)

- **No contact recording (not yet visited)** — confirmed 2026-10-10:
  - The account appears on the day its `due_date` is reached.
  - If it is not visited that day, it keeps appearing on the following days, until it is visited, within the same month.
  - When the month ends, the previous month's accounts are archived (see 7.2). Earlier statement: the month is taken from the account's **`created_at`**. Archiving itself is deferred.
- **With contact recording:** scheduled by the latest recording's `next_action_date`. It appears in the due Next Action Plan results only when that date is due.
- **Latest recording:** latest `contact_date`, then highest `id` (approved via the test baseline). Soft-deleted recordings are ignored.
- **Same-day rescheduling:** a morning visit can set a `next_action_date` later the same day. The account must appear again once that time arrives. Compare date **and time**.
- **Future-dated next actions** never appear in today's due Next Action Plan results.

### 7.1 When an account appears; `period=daily` and `period=monthly` (Confirmed 2026-10-10)

Evaluated as of the run time, in `Asia/Manila`:

1. **Visited** means the account has a contact recording that is not soft-deleted. `accounts.is_contacted` is set on the first recording (7.3) but is a historical flag — it does not go back to `false` if recordings are deleted — so eligibility reads the recordings themselves.
2. **Force-prioritized accounts follow the same rules.** Force prioritization affects classification/priority only; it never bypasses scheduling eligibility. For Repossession still cannot be overridden by Force Prioritized (classification, Step 3).
3. **Unvisited account** (by `due_date`, always within the current month):
   - `daily`: appears when its due date has arrived (`due_date <= today`). Due in the future → hidden. If it stays unvisited it appears again on every later day of the month.
   - `monthly`: also includes accounts due later in the month. A due date in a future month is never included.
4. **Visited account:** only its latest recording's `next_action_date` counts; once visited the account leaves the unvisited pool.
   - `daily`: appears when its date **and time** have arrived. Never future-dated schedules.
   - `monthly`: also includes next actions scheduled later in the current month. A next action in a future month is never included.
5. **Carry-over of an uncompleted next action:** a next action stays eligible for `next_action_carry_over_days` more days (**1**, `config/visit_plan.php`), but never reaches into the previous month. Example (carry-over 1, today 10 Oct): next action on 10 Oct and not visited → shown on 10 Oct and again on 11 Oct, not on 12 Oct. A next action from 9 Oct is shown on 10 Oct, not on 11 Oct.
6. **Month boundary:** an account is not included because its due date or next action belongs to a previous month (monthly archiving itself is deferred, see 7.2).
7. **`daily` ⊆ `monthly`.** Example, today 10 Oct: an account scheduled 10 Oct appears in both; one scheduled 15 Oct appears only in `monthly` (and in `daily` from 15 Oct); one scheduled 2 Nov appears in neither.

Superseded: the first 2026-10-10 answer said monthly only shows schedules that have already arrived, so daily and monthly would match. The later answer above replaces it.

### 7.2 Monthly archiving (Confirmed 2026-10-10 — NOT IMPLEMENTED, deferred)

Record only. Do not add archive logic, change assignment logic or change the schema until approved.

- Every newly assigned account is expected to have a `due_date` within its assignment month; an account assigned in the current month should not have a due date in the following month.
- A next action plan may be scheduled for a later date within the same month.
- When the month ends, all accounts of that month's assignment must be archived.
- Archived accounts must not carry over into the next month's active Visit Plan.
- New accounts arriving for a new month are new assignments for that month, following the LUCAS integration and assignment rules.
- Example: when October ends, October's accounts are archived; November's active Visit Plan uses the November assignments.

### 7.3 `accounts.is_contacted` — initial contact status (Confirmed and implemented 2026-10-11)

- It becomes `true` when the account is visited for the first time, i.e. when its **first contact recording** is created, and it stays `true` (later recordings, or deleting the recording, do not change it).
- Implemented in the `ContactRecording` model (`booted()`, `created` event): a conditional update `where is_contacted = false`, so later recordings do not rewrite the account. Rows added with `insert()` bypass model events, so use `create()`.
- No backfill was done: accounts that already have recordings but `is_contacted = false` in an existing database keep that value.
- Visit Plan eligibility (7.1) still decides "visited" from the contact recordings (not soft-deleted), not from this flag.

### 7.4 `contact_recordings.is_contacted` — FUTURE feature (column added 2026-10-11; behavior not implemented)

- Mirrors `accounts.is_contacted` (`boolean`, `default false`, indexed — migration `2026_10_11_030000_add_is_contacted_to_contact_recordings_table`). Intended meaning: whether the account associated with the recording was visited according to the recording's **next action plan and date**. `false` = not visited (yet).
- **Do not implement or change this behavior until it is requested.** Nothing sets it, and the first-contact rule (7.3) does not touch it.
- Existing recordings keep their data and start as `false`; no backfill was done. It is stored and returned by the Visit Plan retrieval (`contact_recording_is_contacted` for the latest recording, `is_contacted` on each `contact_history` entry), and the sample data marks the recordings that were followed by a later visit. Eligibility (7.1) does **not** use it.

## 8. Out of scope — future workflows (Confirmed)

Record only; do not implement, delete data, or change account status for these:

| Value | Future behavior |
|---|---|
| Action taken `COLLECTED` | Proceeds to receipt processing; `send_date` set by that workflow |
| `FOR INSURANCE CLAIM` | Deferred / completed workflow |
| `FOR ENDORSEMENT TO RCD` | Transfers the account only to the creditor/company that selected it |
| `FOR RESTRUCTURING` | External integration |
| `FOR LEGAL/SMALL CLAIMS` | Company A integration; Company B uses `FOR ENDORSEMENT TO RCD` and transfers to Company A |
| `FOR REPO` | Needs higher-up approval and a due date before it becomes `for_repossession` |
| `is_force_prioritized` reset | May be cleared after a completed contact recording |

## 9. Open questions

1. ~~Order inside the base tier.~~ **Resolved 2026-10-11:** `fdd_ns` > `priority_1_accounts` > `dpd_1_30_days` > `np_1` > `np_0` (section 5).
2. ~~NP0 in any bucket?~~ **Superseded 2026-10-11:** `NP0` is the `DPD 0` bucket; NP is not used (4.2).
3. ~~Combinations no rule covers.~~ **Resolved 2026-10-11:** every bucket now has a classification on its own (4.2 table); NP never excludes an account.
4. ~~Next action not yet due: show or hide?~~ **Resolved 2026-10-10: hidden until its date and time arrive** (7.1).
5. ~~Overdue next actions: still due?~~ **Resolved 2026-10-10: not carried over automatically** (7.1). One conflict remains, see Open question 15.
6. `FOR REPO` approval: is `repossession_requests.status = 'APPROVED'` (or the third-level approval) the signal?
7. ~~`current_category` null on a fallback plan.~~ **Resolved 2026-10-11:** use the base classification (section 6).
8. Out-of-scope next action plans and recordings without `next_action_date`: **deferred to a later feature** (2026-10-11). For now an out-of-scope plan falls through to the base classification; a recording without a next action date is not scheduled (7.1).
9. ~~Plans without a dedicated group (`FOR REVISIT/CALL`, `FOR COLLECTION`, unapproved `FOR REPO`).~~ **Resolved 2026-10-11:** base classification (section 5). `FOR SUPPORT CC VISIT`, `FOR CH VALIDATION` and `FOR EXTERNAL AGENCY` are treated the same way until told otherwise.
10. Monthly archive (7.2, deferred): apply by query exclusion or a stored flag? Basis `created_at` or assignment month? Does it also apply to visited accounts that still have a pending next action?
11. ~~What `period=monthly` should return.~~ **Resolved 2026-10-10** (7.1).
12. Separate rules database: connection, owner of credentials, caching, behavior when unavailable.
13. Response contract: keep the nested `prioritization.next_action_plan.*` shape? Rename `for_skiptrace` → `skiptrace` (breaks the mobile client)?
14. ~~`daily` vs `monthly`.~~ **Resolved 2026-10-10:** `daily` ⊆ `monthly`; monthly adds future schedules within the month (7.1).
15. ~~Next action due yesterday.~~ **Resolved 2026-10-10:** it shows for one more day, `next_action_carry_over_days` = 1 (7.1 point 5).
16. A next action that was due but never visited (and is no longer shown) is not tracked anywhere. Is that acceptable until archiving exists?
17. ~~Force-prioritized accounts and eligibility.~~ **Resolved 2026-10-10:** same rules (7.1 point 2).
18. ~~Plans without a group now pass eligibility.~~ **Resolved 2026-10-11** by the classification step (questions 8 and 9).
19. ~~Application timezone.~~ **Done 2026-10-11:** `config/app.php` is `Asia/Manila`. Still open: existing timestamps stored as UTC before this change (any shared or production database) are 8 hours off; and API date strings are now Manila local time instead of UTC — check the mobile app before release.
20. ~~`accounts.is_contacted`~~ **Done 2026-10-11** (7.3): set on the first contact recording. `contact_recordings.is_contacted` (7.4) is a **deferred future feature**: when it is built, decide who sets it (proposal: marking the previous recording when a later one is saved), and whether Visit Plan eligibility should use it instead of "a later recording replaced it". Also decide whether existing accounts that have recordings but `is_contacted = false` should be backfilled.
21. ~~For Repossession and NP.~~ **Resolved 2026-10-11:** For Repossession is defined by the DPD bucket alone (`DPD 91-120` and higher, never FDD/NS); NP is not used anywhere in the seeded rules.
22. **Accounts that cannot be classified** are now only those with a missing or unknown `dpd_bucket` (a data error: the value is not in the list in 2.1). They are left out of the response and listed in one log warning (count and up to 50 account numbers) so the data issue can be fixed; no arbitrary classification is assigned.
23. `team-assigned-accounts` moved to the rules table and the section 7.1 scheduling on 2026-10-11 (confirmed): the team is defined by the requester's level (2 = `assigned_ch_id`, 3 = `assigned_am_ar_id`, 4 = `assigned_rm__dh_id`) within the required `psgc_code`; accounts are grouped per credit counselor with the same classification and `visited` list as `assigned-accounts`; the requester's level must be 2, 3 or 4 (same check as `ForceAssignmentRequest`); `user_id` is not checked against the authenticated user (known gap). `team-assigned-accounts-psgc` still uses the old `AllocateAccountsHelper` classification and moves in a later phase. The `assigned-accounts-psgc` endpoint moved to the rules table on 2026-10-11: monthly accounts only (no `period`), grouped by `"psgc_code - city"`, each account with its rules-table classification (`null` when no rule classifies it).
24. ~~PSGC endpoint labels differ from the specification labels.~~ **Resolved 2026-10-11 for `assigned-accounts-psgc`:** it uses the same labels as `assigned-accounts`. Still open for `team-assigned-accounts-psgc`.

## 10. Classification rules table — `account_classification_rules` (implemented 2026-10-11)

The rules in sections 4-6 are data, read on every request of `GET /api/v1/dashboard/assigned-accounts` (daily and monthly) by `App\Services\SettingService`. Changing a row changes the result at the next request — no code change and no cache to clear.

| Column | Meaning |
|---|---|
| `parent` | `Prioritization` or `Regular`; must match the classification |
| `classification` | One of the labels in 4.1/4.2. `NULL` = "use the recording's `current_category`" (the fallback plans, section 6) |
| `precedence` | Evaluation order, lowest first, first match wins. Must be set and unique among active rules |
| `conditions` | `{"all": [{"field": …, "operator": …, "value": …}, …]}`, every condition must hold |
| `is_active`, `deleted_at` | Inactive or soft-deleted rules are ignored |

- **Fields:** `dpd_bucket`, `payment_status`, `is_force_prioritized`, `next_action_plan`, and `np` (non-payments converted from the stored text: `UPDATED` = 0, `LAST PAID n MONTH(S) AGO` = n; unrecognised text never matches). **No seeded rule uses `np`**; it stays available so a rule row can use it if the business ever asks.
- **Operators:** `=`, `!=`, `in`, `not_in`; `>`, `>=`, `<`, `<=` on `np` only.
- **Labels and response positions** are in the table `account_classifications` (`parent`, `label` (unique), `response_path`, `is_active`, soft deletes). `response_path` is where the label's accounts appear in the `assigned-accounts` response, e.g. `["regular", "np_1"]`; it is the API contract with the mobile app. The baseline rows are in `AccountClassificationSeeder::BASELINE` (insert-only). A new label needs a mobile-app change (see 10.1).
- **Seeded rules:** `AccountClassificationRuleSeeder` (also called by `DatabaseSeeder`) — precedence 10 For Repossession (`dpd_bucket in DPD 91-120 … DPD MORE THAN 360` and `payment_status not_in FIRST DUE DATE, NON-STARTER`), 20 Forced Prioritized, 30 Promise To Pay, 31 Skiptrace, 32 Notice & Demand Letter, 33 fallback (`NULL`), 40 FDD/NS, 50 Priority 1 Accounts (`DPD 61-90`), 60 DPD 1-30 Days (`DPD 1-30`), 70 NP1 (`DPD 31-60`), 80 NP0 (`DPD 0`). Re-running it resets these rows to the values above.
- **Invalid or missing configuration** (no active rule, unknown field/operator/classification, wrong parent, missing/duplicate precedence, malformed conditions, table not migrated): the endpoint returns HTTP 500 `{"message": "Account classification rules are not configured correctly."}` and the details are logged (`Account classification rules are invalid: …`). There are no default rules.
- **Unclassifiable accounts** (missing or unknown `dpd_bucket`): left out of the response; one warning per request lists their count and up to 50 account numbers (Open question 22).
- **Deploying:** run `php artisan migrate` (adds `precedence`) and `php artisan db:seed --class=AccountClassificationRuleSeeder` on every environment; without rows the endpoint returns the error above.

### 10.1 Maintaining the rules: when a code change is needed

**Data only, no code change** (edit a row, then check the log for `Account classification rules are invalid`):

| Change | Where |
|---|---|
| Change a rule's conditions, `precedence` or `is_active` | row in `account_classification_rules` |
| Add a rule that uses an existing label | new row in `account_classification_rules` with a unique `precedence` |
| Turn a rule off or remove it | `is_active = false` or soft delete |
| Change which DPD buckets, payment statuses or next action plans a rule matches | the rule's `value`; spell it exactly as received from LUCAS (section 2) |

**Needs a code change:**

| Change | What to do |
|---|---|
| New label (new classification) | Add a row to `account_classifications` (`label`, `parent`, `response_path`) and the rule row. Add the label to `AccountClassificationSeeder::BASELINE` so new databases and tests have it. The mobile app must handle the new `response_path` first. (`assigned-accounts-psgc` returns the label as plain text.) Once the back office manages the data (10.2), the admin adds the label and its `response_path` there instead. |
| Move a label to another place in the response | Mobile contract change: update `account_classifications.response_path` and the mobile app together. |
| Rule on a field that is not in the list (`np`, `dpd_bucket`, `payment_status`, `is_force_prioritized`, `next_action_plan`) | (1) select the column in each `DashboardRepository` Visit Plan query that selects `accounts.dpd_bucket`; (2) add the key to the account arrays in `DashboardService` (`assignedAccounts`, `assignedAccountsByPSGC`, `teamAssignedAccounts` via `teamAccountEntry`); (3) add it to `SettingService::FIELDS` and `facts()`, and to `validatedValue()` if it is not text; (4) add tests and update this section. |
| New operator | `SettingService::OPERATORS`, `holds()` and the condition validation. |
| Change how `np` is converted from text | `SettingService::nonPayments()`. |
| Change the evaluation: all conditions must hold, lowest `precedence` wins, fallback to the recording's `current_category` | `SettingService`; needs the owner's approval first. |

**A rule row is valid only if:**

- `conditions` is `{"all": [{"field", "operator", "value"}, ...]}` with a non-empty list.
- `field` is one of the five fields. `operator` is `=`, `!=`, `in` or `not_in`; `>`, `>=`, `<`, `<=` only on `np`.
- `in` / `not_in` take a non-empty list. `np` is a whole number, `is_force_prioritized` is true or false, every other field is text.
- `precedence` is set and unique among active rules.
- `classification` exists and is active in `account_classifications`, and `parent` matches the classification's parent (case-insensitive). `classification = NULL` is the fallback rule (section 6).

**Warnings:**

- One invalid active rule makes every Visit Plan classification endpoint return HTTP 500 (there are no default rules).
- Run `AccountClassificationRuleSeeder` only to set up an environment (see Deploying). Do not rerun it on a database whose rules were edited: it resets the seeded rules, which it matches by `parent` + `classification`. `AccountClassificationSeeder` is insert-only and safe to rerun.
- If a new or changed rule should also exist on new databases and in tests, update `AccountClassificationRuleSeeder` and the counts asserted in `SettingServiceTest`.
- Rule changes are business-rule changes: get the owner's approval first, update sections 4-6 of this file once it is confirmed, and run `php artisan test tests/Feature/VisitPlan`.

### 10.2 Back-office sync (planned, not built)

**Status (2026-10-14): no sync exists.** There is no scheduler, job, HTTP client or inbound route for it. Until it is built, rules are edited in the Mobile CMS tables as described in 10.1.

**Confirmed decisions for the future sync endpoint (2026-10-14):**

1. The back office becomes the source of the rows in `account_classification_rules` and `account_classifications`.
2. **The back office supplies `response_path` (changed 2026-10-14; it replaces the earlier decision that only Mobile CMS owns it).** When an admin creates a classification in the back office, the admin picks `response_path` from the paths already known, or adds a new path there when the new classification needs a new position in the response. The Mobile CMS API needs no code change for a new path (the response tree is built from the table); the mobile app must be able to show it, so the admin confirms that support when adding a new path.
3. **An invalid payload is rejected as a whole and the last good rules stay active.** The failure is logged. This does not create default rules.
4. The condition fields stay the five listed in this section. A new field is a code change (10.1).
5. **Deleting a classification that still has active rules (2026-10-14):** the back office blocks it by default. A separate, confirmed "retire together" action retires the rules first and then the classification, in one transaction. "Delete" is a soft delete.

The back office CRUD, the identifiers to preserve and the seeder transition are described in `docs/backoffice-classification-crud-guide.md`.

**Open:**

- **Delivery method:** the back office pushes to an endpoint, Mobile CMS pulls on a schedule (needs a Render Cron Job; none exists), or an import from an export file. Decide before building.
- Any HTTP entry point is security-sensitive: it needs its own credential (the existing `api.key` is `APP_KEY`, shared by every caller), throttling and audit logging, and approval.
- **Proposed, not confirmed:** match rows by `label` (classifications) and `precedence` (rules), send the full snapshot, and soft-delete rows missing from it. Confirm the payload with the back-office team.

**Once sync is live:** do not edit these tables by hand and do not run the rule seeder, because the next sync overwrites hand edits.
