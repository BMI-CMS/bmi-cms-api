# Backoffice Guide: CRUD for `account_classifications` and `account_classification_rules`

Audience: AI coding agents building the Backoffice master maintenance module for account classifications and their rules.
Last updated: 2026-10-14.

Words used: **Verified** = read in this repository (source named). **Recommended** = good practice proposed here, not a confirmed project rule. **Approval** = do not do it unless the project owner approves. **Open** = not decided; ask, do not guess.

---

## 1. Architecture and decisions

**Intended architecture (stated by the project owner, 2026-10-14):**

- The Backoffice and the Mobile CMS API have **separate databases** with the **same structure** for `account_classifications` and `account_classification_rules`. Treat them as one logical structure. Do not add columns or tables.
- Administrators maintain the data in the Backoffice instead of editing seeders. The Backoffice database is the master.
- At a later time the Mobile CMS API synchronizes the data into its own copies and uses it to classify accounts (`app/Services/SettingService.php`).
- **Out of scope here:** the synchronization, any API or queue, and any change to the Mobile CMS code.
- Create the two tables in the Backoffice with the same migrations as the Mobile CMS so the structure is identical, including `precedence`: `2026_10_07_072049_create_account_classification_rules_table`, `2026_10_11_040000_add_precedence_to_account_classification_rules_table`, `2026_10_12_000000_create_account_classifications_table`.

**Decisions:**

| # | Decision | Status |
|---|---|---|
| D1 | `response_path` is chosen by the admin in the Backoffice from the list of paths already known (see 4.2). If a new classification needs a new position, the admin can add a new path in the Backoffice. This replaces the earlier note that only the Mobile CMS owns it. | Confirmed 2026-10-14 |
| D2 | Deleting a classification that still has active rules is **blocked** by default. A separate, confirmed **"retire together"** action retires the rules first and then the classification, in one transaction (section 6). | Confirmed 2026-10-14 |
| D3 | "Delete" means **soft delete** (both models use `SoftDeletes`). Permanent delete needs approval. | Recommended |

---

## 2. The two tables and their relationship

### 2.1 Columns **(Verified: the three migrations above, and `app/Models/AccountClassification.php`, `AccountClassificationRule.php`)**

**`account_classifications`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, primary key, auto increment | |
| `parent` | string(50) | `prioritization` or `regular`, **lowercase** |
| `label` | string(100), **unique** | Identity of the classification. The unique index also covers soft-deleted rows |
| `response_path` | json, NOT NULL | Where the label's accounts appear in the response, e.g. `["regular","np_1"]` |
| `is_active` | boolean, default true | |
| `deleted_at` | timestamp, nullable | Soft delete |
| `created_at`, `updated_at` | timestamps | |

**`account_classification_rules`**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint, primary key, auto increment | |
| `parent` | string(100) | `Prioritization` or `Regular` (the seeded capitalization) |
| `classification` | string(100), nullable | The `label` the rule assigns. `NULL` = **fallback rule** (use the recording's `current_category`) |
| `precedence` | unsigned smallint, nullable | Evaluation order, lowest first, first match wins. Required on an active rule |
| `conditions` | json, nullable | `{"all": [{"field": ..., "operator": ..., "value": ...}, ...]}` |
| `is_active` | boolean, default true | |
| `deleted_at` | timestamp, nullable | Soft delete |
| `created_at`, `updated_at` | timestamps | |

There are **no foreign keys**, no index on the rules table besides the primary key, no unique constraint on `precedence`, and no `company_id` or user columns. **(Verified)**

### 2.2 Relationship **(Verified: `SettingService::validated()`)**

- One classification has zero or more rules. A rule points to its classification by the **text** of `rules.classification` = `classifications.label` (exact, case-sensitive).
- `rules.parent` repeats `classifications.parent`. They must agree ignoring case. Because the code compares the lowercased rule parent with the stored classification parent, `classifications.parent` must be stored lowercase.
- The database enforces none of this. The Backoffice application must.

### 2.3 What the Mobile CMS will accept after the sync **(Verified: `SettingService`)**

Only rows with `is_active = true` and `deleted_at IS NULL` are used. An active set that breaks any rule below makes the Visit Plan endpoints return HTTP 500 for every user. There are no default rules.

| # | Rule |
|---|---|
| V1 | At least one active classification and one active rule exist. |
| V2 | Every active classification has a `response_path` that is a JSON list of at least 2 items. |
| V3 | Every active rule has a `precedence`, unique among active rules. |
| V4 | A rule with a non-NULL `classification` points to an **active, non-deleted** classification with exactly that `label`, and the parents agree (2.2). |
| V5 | `conditions` has key `all`, a non-empty list. |
| V6 | Each condition has a `field` and `operator` from the lists in 2.4, and a `value` key. |
| V7 | `>`, `>=`, `<`, `<=` only on `np`. |
| V8 | `in` / `not_in` take a non-empty list; every other operator takes a single value, not a list. |
| V9 | Value types: `np` whole number; `is_force_prioritized` true/false; every other field text. |
| V10 | `label` is unique across all rows, including soft-deleted ones. |

**"Validate the set"** used below means: after the writes and before commit, load the resulting active set and check V1 to V9 and P1 to P3 (2.5). On any failure, roll back and report which check failed.

### 2.4 Allowed condition fields and operators **(Verified: `SettingService::FIELDS`, `OPERATORS`; `docs/general-business-rules.md` sections 2 and 6)**

| Field | Value type | Known values |
|---|---|---|
| `dpd_bucket` | text | `DPD 0`, `DPD 1-30`, `DPD 31-60`, `DPD 61-90`, `DPD 91-120`, `DPD 121-150`, `DPD 151-180`, `DPD 181-210`, `DPD 211-240`, `DPD 241-270`, `DPD 271-300`, `DPD 301-330`, `DPD 331-360`, `DPD MORE THAN 360` |
| `payment_status` | text | `FIRST DUE DATE`, `NON-STARTER`, `WITH PAYMENT` |
| `next_action_plan` | text | `PROMISE TO PAY`, `FOR SKIPTRACE`, `FOR SERVICE OF COLLECTION NOTICE`; fallback plans: `FOR FIELD VISIT`, `FOR FILING BRGY BLOTTER/COMPLAIN`, `TO ATTEND BARANGAY HEARING`, `FOR DISCREPANCY ADJUSTMENT`, `FOR ASSUMPTION OF UNIT`, `FOR WRITE OFF (MINIMAL BALANCE)` |
| `is_force_prioritized` | boolean | `true`, `false` |
| `np` | whole number | Non-payments (not used by any seeded rule) |

Operators: `=`, `!=`, `>`, `>=`, `<`, `<=`, `in`, `not_in`. Store LUCAS values **exactly as received** (no change of case, spelling or plural). A misspelled value never matches and gives no error. A new field or operator is a Mobile CMS code change, so the Backoffice must offer only these.

### 2.5 `response_path` rules

The Mobile CMS only checks that it is a list of 2+ items (V2). It then uses each item as an array key and appends the account at the end of the path (`SettingService::classify()`, lines 67 to 72). So the Backoffice must also enforce:

| # | Rule | Status |
|---|---|---|
| P1 | Every item is a non-empty string; use lowercase snake_case like the seeded paths (e.g. `["prioritization","next_action_plan","force_prioritized"]`). | Recommended |
| P2 | Among **active** classifications no two share a path, and no path equals or starts another path (otherwise the response mixes account lists and keys). | Recommended, derived from `classify()` |
| P3 | The first item equals the classification's `parent`. All 10 seeded paths follow this; it also avoids a clash with the response's top-level `visited` key. | Recommended |

A new classification needs **no Mobile CMS API code change** (the response tree is built from the table; `openapi.yaml`, lines 244 and 974). The **mobile app** (not in this repository) must be able to show the new position; what it does with an unknown path cannot be verified here (Open O3).

---

## 3. Identifiers and fields to preserve

The existing schema is enough to keep the same records consistent in both databases. **No schema change is proposed.** A record is the same record in both databases when it has the same `id`.

| Field | Why it matters | Rule |
|---|---|---|
| `id` (both tables) | The identity of the record in both databases. | Only the Backoffice assigns ids. Never change, reuse or renumber an id. Never truncate or reseed these tables. Never delete and reinsert a row to edit it. |
| `label` | Natural unique key of a classification; its text is copied into `rules.classification` and into the Mobile CMS `contact_recordings.current_category` (history). | Never rename (**Approval**). Restore a soft-deleted label; do not insert it again. |
| `rules.classification` | The link to the classification. | Change only when a rule is re-pointed (**Approval**), together with `parent`. |
| `parent` (both) | Repeated in both tables; must agree. | Lowercase on the classification, `Prioritization`/`Regular` on rules. A change cascades (4.4). |
| `precedence` | Decides which rule wins. | Unique among active rules. Leave gaps; never renumber other rules. |
| `response_path` | The position in the mobile response. | Follow P1 to P3. Changing it needs **Approval**. |
| `is_active`, `deleted_at` | Lifecycle. The Mobile CMS ignores a row if either one is off. | Keep the rows and carry the state; do not remove rows. |
| `conditions` | The rule logic. | Replace the whole JSON value; keep the shape in V5 to V9. |
| `created_at` / `updated_at` | History. | `created_at` never changes. `updated_at` is set on every change, including delete and restore. |

**The one gap:** a rule has no second key. `precedence` is unique only among active rules and can change. If the future sync cannot preserve `id`, rules would have no stable key across the databases. That would be the specific reason to revisit the schema, and it should be raised then (Open O1).

---

## 4. CRUD for `account_classifications`

Every write below runs in **one transaction** and ends with "Validate the set".

### 4.1 Read

- List all rows including inactive and soft-deleted ones, with their state.
- For each classification show its rules: `account_classification_rules` where `classification` equals the `label` (join by text, there is no foreign key).

### 4.2 Create (tables affected: `account_classifications`, one insert)

1. Input: `parent`, `label`, `response_path`, `is_active` (default true).
2. `parent`: `prioritization` or `regular`, lowercase.
3. `label`: exact text, at most 100 characters. Look it up among **all** rows, soft-deleted included:
   - found and active: reject as a duplicate;
   - found and soft-deleted: do not insert; offer **restore** (4.5);
   - **Recommended:** also reject a label that differs only by letter case.
4. `response_path` (D1). The admin either:
   - **picks** a path from the list of paths already in the table (distinct `response_path` values of all rows, including inactive and soft-deleted ones, so a retired label's position can be reused; paths held by an active classification cannot be picked again, P2), or
   - **adds a new path** when the new classification needs a new position. Validate P1 to P3. The admin must confirm that the mobile app supports the new position, and the confirmation should be recorded (**Recommended**).
5. Insert the row. Validate the set.
6. Result: the classification exists but is not used until a rule points to it. No other table changes.

> If the owner later wants a curated list of approved paths kept in its own table, that is a new table and needs a specific decision (Open O5). Until then the list is derived from the existing rows.

### 4.3 Update (tables affected: `account_classifications`; rules only for a `parent` change)

Edit the row in place. Set `updated_at`.

| Field | Procedure |
|---|---|
| `is_active` to false | Only if the classification has no active rule. Otherwise use section 6. |
| `is_active` to true | Validate the set (V2, P2). Its rules stay off until reactivated one by one. |
| `parent` (**Approval**) | In the same transaction update `parent` of **every** rule whose `classification` is this label (all states) and the first item of `response_path` (P3). |
| `response_path` (**Approval**) | Moves accounts inside the mobile response. Same choices as 4.2 step 4. |
| `label` (**Approval**, prohibited by default) | Safer: create a new label (4.2) and retire the old one (section 6). If approved, update `classification` of every rule of the label (all states) in the same transaction; the Mobile CMS keeps old `current_category` text in its history. |
| `id`, `created_at` | Never. |

### 4.4 Delete (soft delete)

- **No active rules:** set `deleted_at` and `is_active = false` on this row only (**Recommended**: set both so a restore does not switch it on by accident). Inactive or deleted rules that still mention the label stay as they are; the Mobile CMS ignores them. Validate the set.
- **Active rules:** do not delete. Go to section 6.
- **Permanent delete:** **Approval**. If approved, delete its rules first, then the classification.

### 4.5 Restore

Clear `deleted_at` on the existing row (never insert a new one) and set `is_active = true` on purpose. Its rules stay off. Validate the set.

---

## 5. CRUD for `account_classification_rules`

Every write runs in **one transaction** and ends with "Validate the set".

### 5.1 Read

List rules ordered by `precedence`, with `classification`, state and a readable summary of `conditions`. Show inactive and deleted rules separately.

### 5.2 Create (tables affected: `account_classification_rules`, one insert; `account_classifications` is only read)

1. Choose the classification: it must exist, be active and not deleted (V4). Choose none only for a fallback rule (5.3).
2. `parent`: the classification's parent with the seeded capitalization (`Prioritization` / `Regular`).
3. `precedence` (**Approval** for the position): a number 0 to 65535 not used by another active rule. The lowest number wins, so the position decides which accounts the rule takes. Leave gaps (the seeded values are 10, 20, 30, 31, 32, 33, 40, 50, 60, 70, 80). Never renumber other rules.
4. `conditions`: build from the lists in 2.4 only, with exact LUCAS values.
5. **Conditional:** `!=` and `not_in` conditions also restrict the recorded-category fallback for that label (`SettingService::recordedCategory()`). Add them deliberately.
6. Insert with `is_active = true`. Validate the set.
7. Show the impact: a rule with a lower `precedence` than others can take accounts from them.

### 5.3 Create a fallback rule (`classification = NULL`)

It means "use the label saved in the recording's `current_category`" (`docs/general-business-rules.md` section 6). It is a business rule: **Approval**. `parent` is required but not checked against any classification. The usual condition is `next_action_plan in [...]` with the confirmed fallback plans. Same steps as 5.2 from step 3.

### 5.4 Update (edit in place, one row)

| Field | Procedure |
|---|---|
| `conditions` | Replace the whole value. Validate. Show the impact. |
| `precedence` (**Approval**) | Final state must keep V3. To swap two rules, update both in one transaction. Do not renumber others. |
| `classification` (**Approval**) | Target active and not deleted; update `parent` in the same update (V4). |
| `parent` | Only together with `classification`, or to fix capitalization. |
| `is_active`, `deleted_at` | Through delete and restore (5.5, 5.6). |
| `id`, `created_at` | Never. |

### 5.5 Delete (soft delete)

Nothing in either table references a rule `id`, so the database has no dependents. **(Verified)** But it changes how accounts classify.

1. **Impact check:** which source values (for example each `dpd_bucket`) are still matched by another active rule? An account no rule classifies is left out of the `assigned-accounts` response and logged, and `assigned-accounts-psgc` returns it with `account_classification = null` (`docs/project-status.md`). If values become uncovered, show them and require confirmation (Open O7).
2. **Conditional:** if the rule has `!=` / `not_in` conditions, tell the admin that this guard on the label is lost.
3. If it is the last active rule, stop (V1).
4. Set `deleted_at` and `is_active = false`. Validate the set.

To switch a rule off temporarily, only set `is_active = false`.

### 5.6 Restore

Check that `precedence` is not used by another active rule, the classification is active and not deleted, and `parent` agrees. Clear `deleted_at`, set `is_active = true`, validate the set.

---

## 6. Deleting a classification that still has rules

The Mobile CMS fails (V4) if an active rule points to a classification that is inactive or deleted. So the Backoffice must never allow that state (D2).

**Default: block.**
1. List the classification's active rules (id, precedence, conditions summary).
2. Refuse the delete and tell the admin to remove or move those rules first (5.5, 5.4).

**Explicit action: "retire together"** (only after the admin confirms the impact):
1. Open one transaction.
2. Show the active rules and the impact (5.5 steps 1 and 2). Require confirmation.
3. **Rules first:** set `deleted_at` and `is_active = false` on every active rule whose `classification` is this label. Name the rule ids; change nothing else.
4. **Then** the classification (4.4).
5. Validate the set (V1 to V9, P1 to P3). Commit, or roll back everything.

Never do these in the other order.

**Not touched:** rules of other labels, fallback rules (`classification = NULL`), and inactive or deleted rules of this label. After the retirement, accounts whose latest recording still has this label in `current_category` fall to their base classification; that history is in the Mobile CMS database and is not changed.

---

## 7. Seeder transition

The two tables are filled by seeders today. Once administrators own the data, the seeders become a risk.

### 7.1 What the seeders do today and what breaks **(Verified)**

| Item | Behavior today | Problem once admins own the data | Action in the transition phase |
|---|---|---|---|
| `AccountClassificationRuleSeeder` | Calls `AccountClassificationSeeder` first. For each of the 11 rules: `withTrashed()->updateOrCreate` matched by (`parent`, `classification`), sets `precedence`, `conditions`, `is_active = true`, and restores a soft-deleted row. | Overwrites admin edits, brings deleted rules back, switches deactivated rules on, and inserts a second seeded row if an admin changed a rule's parent, classification or precedence (this can duplicate a precedence and cause HTTP 500). New rows get local ids. | Never run it on a database that receives Backoffice data. Keep it for tests and development. |
| `AccountClassificationSeeder` | 10 labels, `withTrashed()->firstOrCreate` by `label` (insert-only), `response_path` from `BASELINE`. | Re-creates a baseline label that was removed, with a new local id, and with the `BASELINE` path. | Same. |
| `DatabaseSeeder` | Calls the rule seeder (development). | Development data differs from the Backoffice data. | Acceptable for development and tests. |
| `ProductionSeeder`, `RUN_SEEDERS=true` in `scripts/00-laravel-deploy.sh` | Runs once, only if no company exists; includes the rule seeder; resets PostgreSQL id counters of empty tables. | A new environment gets the seeded baseline (ids 1 to 10 and 1 to 11) before any Backoffice data arrives. | Decide per new environment: keep the baseline as a start, or leave the tables empty until the first sync (the endpoints return HTTP 500 while empty, V1). |
| Tests | `VisitPlanTestCase` seeds the rule seeder. `SettingServiceTest` asserts exactly 11 rules, unique precedence, one NULL fallback, and no duplicates on re-seed. `AssignedAccountsPsgcTest` maps `response_path` to label for every classification row. `VisitPlanBaselineTest` force-deletes rules. | They need a fixed baseline. | Keep the seeders as test fixtures. If the baseline changes, update these expectations. |
| Docs and comments | `docs/general-business-rules.md` section 10 "Deploying" and 10.1 (new label row) and `docs/project-status.md` line 49 say to run the seeder; the seeder's own comment says "Safe to rerun ... reset to these values". | They tell people to reset the data. | Revise at the transition. |

### 7.2 Ids at the start

Consistency needs the Backoffice rows to start **identical** to the Mobile CMS rows, with the same `id`.

- On a freshly seeded empty database the ids follow insertion order: classifications 1 to 10 in `BASELINE` order, rules 1 to 11 in the seeder's order. **(Verified in the seeder code; not verified against Render or any existing database.)** The rules table existed before the current seeder, and an earlier version of the seeder failed (`docs/project-status.md`), so ids in an existing database may differ.
- Recommended: copy the current rows, with their ids, from the Mobile CMS database into the Backoffice, then compare `id`, `label`, `parent`, `precedence`, `conditions`, `response_path` and flags.
- Alternative: seed an empty Backoffice database with the same two seeders and compare with the Mobile CMS rows before the first admin edit.
- After the sync copies explicit ids into PostgreSQL, its id counter does not advance by itself. This matters for the sync phase and is not designed here.

### 7.3 Transition checklist (future phase, needs owner approval; nothing is changed by this guide)

1. Create the two tables in the Backoffice with the same migrations (section 1).
2. Load the baseline with the same ids and compare it with the Mobile CMS data (7.2).
3. Release the Backoffice CRUD. From then on, nobody edits these tables by hand in either database.
4. When the sync is live, stop running the two seeders outside tests (a separate Mobile CMS task).
5. Revise the docs and comments in 7.1.

---

## 8. Safeguards checklist

1. **Transaction** for every write that touches more than one row or table, and for any change that can affect other rows' validity.
2. **Validate the whole resulting active set** (V1 to V9, P1 to P3) before commit. Roll back on any failure. No partial updates.
3. **Same allowlists as the Mobile CMS:** fields, operators and value types (2.4). They are copied from `SettingService`; re-check them when `SettingService` changes.
4. **Edit in place.** No delete-and-reinsert, no bulk replace, no truncate, no reseed.
5. **Name exact ids** in every update and delete. Never renumber `precedence` of unrelated rules.
6. **Store LUCAS values exactly** (2.4).
7. **`precedence` uniqueness under concurrent edits:** there is no unique constraint and the structure must stay the same, so serialize writes in the application (for example lock the active rules while saving).
8. **Warn on duplicates:** two active rules with the same classification and identical conditions are allowed by the schema and are almost always a mistake (**Recommended**).
9. **Audit** old and new values, user and time in the same transaction (**Recommended**). The Mobile CMS has an `audit_logs` table that nothing writes to; the Backoffice audit design is Open.
10. **Approval and roles:** precedence position, label/parent/path changes, new paths, fallback rules and "retire together" change how accounts are classified for field users. Restrict them to authorized roles (Open O6).
11. **Do not touch the Mobile CMS database** from Backoffice code.

---

## 9. Open items

| # | Item | Meanwhile |
|---|---|---|
| O1 | Will the sync preserve `id`? | Assume yes; never change or reuse ids. |
| O2 | Are the ids in the Mobile CMS databases (including Render) the baseline ids? | Compare before the first admin edit (7.2). |
| O3 | What does the mobile app do with an unknown `response_path`? | Require the admin to confirm app support for a new path. |
| O4 | May two active classifications share one position? | No (P2). |
| O5 | Should approved paths be a curated list in a table? | Derive the list from existing rows; no new table. |
| O6 | Which roles may maintain this data; what is the audit schema? | Restrict to authorized roles; record old/new values. |
| O7 | Must a save be blocked when a source value is left without a rule? | Warn and require confirmation. |
| O8 | Is permanent delete ever allowed? | No; soft delete only. |
| O9 | Timestamp timezone in the Backoffice? | Use Asia/Manila like the Mobile CMS (`docs/general-business-rules.md` section 1). |

---

## Source map

| Topic | File |
|---|---|
| Tables | `database/migrations/2026_10_07_072049_create_account_classification_rules_table.php`, `2026_10_11_040000_add_precedence_to_account_classification_rules_table.php`, `2026_10_12_000000_create_account_classifications_table.php` |
| Models | `app/Models/AccountClassification.php`, `app/Models/AccountClassificationRule.php` |
| What the Mobile CMS validates and evaluates | `app/Services/SettingService.php` |
| Seeders | `database/seeders/AccountClassificationSeeder.php`, `AccountClassificationRuleSeeder.php`, `DatabaseSeeder.php`, `ProductionSeeder.php`; `scripts/00-laravel-deploy.sh` |
| Tests that use the seeders | `tests/Feature/VisitPlan/VisitPlanTestCase.php`, `SettingServiceTest.php`, `AssignedAccountsPsgcTest.php`, `VisitPlanBaselineTest.php` |
| Business rules and decisions | `docs/general-business-rules.md` (sections 1 to 6, 10) |
