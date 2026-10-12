# Mobile CMS --- Visit Plan Feature Rules and Implementation Context

## 1. Objective and scope

Implement the **Visit Plan** feature for the Mobile CMS API. Treat this
document as the business-rule reference for this feature.

-   Focus only on the Visit Plan feature for the current implementation.
-   Some account actions described below will integrate with other
    systems later. Record those rules for future reference, but **do not
    implement those integrations or downstream processing now**.
-   Use `snake_case` for JSON response keys and other API response
    fields.
-   Make business rules **parameterized/configurable**, rather than
    scattering hard-coded classification conditions throughout
    controllers or services.
-   Keep the classification rules/configuration in a **separate
    database** from the application's main database, as requested.
    Before implementation, inspect the existing project architecture and
    configuration. In Plan Mode, propose the connection/configuration
    and data structure, identify operational implications, and ask for
    approval before making changes. Do not silently assume that
    "separate database" means a separate database server; it may be a
    separate database/schema/connection.
-   Follow the existing project conventions and approved security
    requirements. Do not refactor unrelated features.

## 2. Account classification groups

There are two main groups:

1.  **Prioritization Accounts**
2.  **Regular Accounts**

Every account included in Visit Plan classification must receive exactly
one final classification. Apply the precedence rules in Section 6 so
that one account cannot appear in multiple classification categories.

### 2.1 Prioritization Accounts

#### A. `promise_to_pay`

An account becomes `promise_to_pay` only after it has a contact
recording and its `next_action_plan` is `PROMISE TO PAY`, and the
recorded `next_action_date` is due for the Visit Plan being generated.

Example: 1. An account is initially classified as `DPD 1-30 Days`. 2. A
user visits the account and saves a contact recording. 3. The contact
recording stores `DPD 1-30 Days` as `current_category`, `PROMISE TO PAY`
as `next_action_plan`, and the customer's promised payment date as
`next_action_date`. 4. Before the scheduled date is due, do not classify
it as `promise_to_pay` in the due Next Action Plan results. 5. When the
scheduled date becomes due, classify it as `promise_to_pay`.

The contact recording's `current_category` preserves the account's
underlying/base category. The due next action plan determines the Visit
Plan classification when its rule applies.

#### B. `for_repossession`

There are two ways an account can qualify for `for_repossession`:

1.  **Rule-based qualification:** `no_of_non_payments >= 3` and
    `dpd_bucket = "DPD 91-120"`.
2.  **Approved next-action-plan qualification:** the account has a
    contact recording with `next_action_plan = "FOR REPO"`, the request
    has been accepted by the appropriate higher-up/approver, and the
    corresponding `next_action_date` is due.

The approved-next-action-plan workflow is future integration work. For
the current Visit Plan implementation, do not invent an approval field
or approval process if the project does not already contain one.
Identify the missing approval data/contract in Plan Mode and ask for
clarification before implementing that path.

`for_repossession` has the highest classification precedence. **Force
prioritization must not override an account that qualifies for
`for_repossession`.**

#### C. `priority_1_accounts`

An account qualifies when both conditions are true:

-   `no_of_non_payments >= 2`
-   `dpd_bucket = "DPD 61-90"`

#### D. `fdd_ns`

An account qualifies when its payment status is either:

-   `FIRST DUE DATE`
-   `NON-STARTER`

For this classification, ignore `no_of_non_payments` and `dpd_bucket`.
However, an account may still be force-prioritized by an authorized
higher-up. The exception is an account that qualifies for
`for_repossession`: repossession takes precedence over force
prioritization.

Do not infer additional FDD/NS rules beyond those specified here.

#### E. Next Action Plan classifications

These classifications apply to accounts with a contact recording and a
matching `next_action_plan` whose `next_action_date` is due.

##### `skiptrace`

When `next_action_plan = "FOR SKIPTRACE"` and the next action date is
due, classify the account as `skiptrace`.

##### `force_prioritized`

When `accounts.is_force_prioritized = true`, classify the account as
`force_prioritized`, subject to the precedence rules. This is different
from `skiptrace`, `promise_to_pay`, and `notice_and_demand_letter`: it
is determined by the flag, not by a due next action plan.

A future feature may set `is_force_prioritized` to `false` after a
contact recording is completed. Do not implement that flag-reset
behavior as part of this task.

##### `notice_and_demand_letter`

When `next_action_plan = "FOR SERVICE OF COLLECTION NOTICE"` and its
`next_action_date` is due, classify the account as
`notice_and_demand_letter`.

The older value `FOR SERVICE OF COLLECTION NOTICE/ATTY'S LETTER` is no
longer used. Do not use it as the current matching value.

### 2.2 Regular Accounts

#### A. `dpd_1_30_days`

Use `dpd_bucket` to determine this classification. The matching bucket
is `DPD 1-30`. Do not calculate this classification from a separate
day-count calculation when `dpd_bucket` is the agreed source of truth.

#### B. `np_0`

This is the regular classification for an account with no non-payments
(`no_of_non_payments = 0`), subject to higher-precedence classifications
and any existing authoritative business rule in the project. If the
existing data model/business logic defines NP0 differently, report the
difference in Plan Mode instead of silently changing the rule.

#### C. `np_1`

This classification applies when `no_of_non_payments = 1` and
`dpd_bucket = "DPD 31-60"`.

#### D. `no_contact_recording`

This classification is discontinued. Do not return it, create new logic
for it, or use it as a fallback classification.

## 3. Allowed `dpd_bucket` values

Treat these as the currently known possible values:

-   `DPD 0`
-   `DPD 1-30`
-   `DPD 31-60`
-   `DPD 61-90`
-   `DPD 91-120`
-   `DPD 121-150`
-   `DPD 151-180`
-   `DPD 181-210`
-   `DPD 211-240`
-   `DPD 241-270`
-   `DPD 271-300`
-   `DPD 301-330`
-   `DPD 331-360`
-   `DPD MORE THAN 360`

Do not assume classifications for buckets not explicitly covered by a
rule. Identify any uncovered combinations and ask how they should be
handled. Do not create a new `no_contact_recording` fallback.

## 4. Allowed payment status values

-   `FIRST DUE DATE`
-   `NON-STARTER`
-   `WITH PAYMENT`

## 5. Fallback to `current_category` for other next action plans

The following next action plan values do not create a dedicated Visit
Plan classification. When such a next action plan is due, use the
contact recording's `current_category` as the account's classification:

-   `FOR FIELD VISIT`
-   `FOR FILING BRGY BLOTTER/COMPLAIN`
-   `TO ATTEND BARANGAY HEARING`
-   `FOR DISCREPANCY ADJUSTMENT`
-   `FOR ASSUMPTION OF UNIT`
-   `FOR WRITE OFF (MINIMAL BALANCE)`

Example: an account has `current_category = "DPD 1-30 Days"` and
`next_action_plan = "TO ATTEND BARANGAY HEARING"`. When its
`next_action_date` is due, its Visit Plan classification remains
`DPD 1-30 Days`.

Only apply this fallback when no higher-precedence classification or
dedicated next-action-plan classification applies.

## 6. Classification precedence

Apply classification rules in this order, from highest to lowest:

1.  **`for_repossession`** --- including rule-based qualification and,
    once the required approval information is available, approved
    `FOR REPO` requests.
2.  **`force_prioritized`** --- `is_force_prioritized = true`, except
    when the account qualifies for `for_repossession`.
3.  **Due next action plan classification** --- for example,
    `promise_to_pay`, `skiptrace`, or `notice_and_demand_letter`; other
    listed next action plans fall back to `current_category`.
4.  **Base classification** --- determined by payment status,
    `no_of_non_payments`, and `dpd_bucket`, according to the rules
    above.

`priority_1_accounts` and `fdd_ns` are base classifications unless a
higher-precedence rule overrides them.

Important: the exact interaction between a due next action plan and
`fdd_ns` is not fully specified. Follow the precedence list above for
now, but call out any conflicting existing implementation or unclear
case in Plan Mode for approval before coding.

## 7. Visit Plan scheduling rules

### A. New accounts without a contact recording

-   If an account has no contact recording, schedule it based on its
    account due date.
-   The account is initially eligible while
    `accounts.is_contacted = false`.
-   If the account is due today but the user does not visit it, it
    should continue to appear on subsequent days within the same month
    until it is handled, subject to the existing data model and business
    rules.
-   At the start of the next month, accounts whose due date was in the
    previous month should be archived and should no longer appear in the
    current Visit Plan.
-   Do not implement the archive mechanism unless the project already
    has the required behavior or it is explicitly approved. In Plan
    Mode, explain how the existing schema supports (or does not support)
    archiving.

### B. Accounts with a contact recording

-   Schedule these accounts based on the relevant `next_action_date`.
-   Include an account in the due Next Action Plan results only when its
    next action date is due for the Visit Plan date being generated.
-   Use the latest applicable contact recording for the scheduled next
    action. If multiple contact recordings exist, do not arbitrarily
    choose one; inspect the existing model/query patterns and propose
    the deterministic rule in Plan Mode.

### C. Same-day rescheduling

An account may be visited in the morning, receive a contact recording,
and be assigned a `next_action_date` later that same day.

The account must be eligible to appear again in the afternoon's Visit
Plan based on the newly recorded next action date. Do not let the
morning visit permanently suppress a valid later same-day scheduled
action.

When selecting a scheduled action, use the date and time where
available. Do not treat a date-only comparison as sufficient if the data
stores a time and the feature needs to distinguish morning from
afternoon.

## 8. Other next action plans --- future reference only

These values represent downstream workflows and should not be
implemented as integrations or terminal processing in the current Visit
Plan task.

### A. `COLLECTED`

An action taken of `COLLECTED` means the account is finished with the
collection action and will proceed to receipt processing; `send_date`
will also be populated in the relevant future workflow. For this task,
do not implement receipt processing or modify `send_date`.

### B. `FOR INSURANCE CLAIM`

Treat this as out of scope and as not part of the active Visit Plan
work, similar to a completed/deferred workflow. Preserve this rule for
future reference. Do not invent a database status or mutate the account
to mark it done unless the existing application already has an approved
mechanism.

### C. `FOR ENDORSEMENT TO RCD`

Treat this as out of scope for the current Visit Plan implementation and
preserve it for future reference. In the future, this next action plan
will transfer the account only to the creditor/company that selected it,
according to the cross-company workflow.

### D. `FOR RESTRUCTURING`

This is a downstream integration workflow. Treat it as out of scope for
the current Visit Plan implementation. Do not implement the external
system integration now.

### E. `FOR LEGAL/SMALL CLAIMS`

This is a downstream integration workflow, available to Company A.
Company B uses `FOR ENDORSEMENT TO RCD` and transfers the account to
Company A for processing. Preserve this distinction for future
integration work; do not implement it now.

### F. `FOR REPO`

This next action plan requests repossession. In the future, the request
must be accepted by the appropriate higher-up/approver, and the due next
action date must be reached before the account is classified as
`for_repossession` through this workflow. This approval/integration
process is out of scope until its data contract is confirmed.

For all future workflows above, do not confuse "out of scope" with
permission to delete data or modify account status. For the current
task, focus on Visit Plan classification and scheduling only.

## 9. Parameterized rules and separate database

-   Store configurable classification/scheduling rules in a dedicated
    rules data store, separate from the main application database as
    requested.
-   Before coding, inspect the repository to determine the framework,
    current database connections, migrations, models, configuration
    conventions, cache conventions, and existing classification logic.
-   In Plan Mode, propose:
    -   the database connection/name and how it will be configured;
    -   the rule tables/columns or configuration structure;
    -   how rules will be validated and versioned/maintained;
    -   how rule changes will take effect;
    -   whether caching is appropriate and how cache invalidation will
        work;
    -   what happens if the rules database or a required rule is
        unavailable.
-   Do not silently create or configure an external database server,
    credentials, or deployment dependencies.
-   Do not hard-code the same business rules in multiple places. Keep
    rule evaluation centralized and testable.
-   Do not use an undocumented default classification for missing,
    unknown, or conflicting rule data.

## 10. API response requirements

-   Use `snake_case` for JSON response keys.
-   Keep the response format consistent with existing API conventions.
-   Ensure each account appears in exactly one final classification in a
    given Visit Plan result.
-   Do not include the discontinued `no_contact_recording`
    classification.
-   Do not return accounts with future-dated next actions in today's due
    Next Action Plan results.
-   Keep account data and contact-recording history consistent with the
    existing API contract. Do not introduce unrelated response changes.

## 11. Required workflow for Claude Code

**Start in Plan Mode. Do not implement immediately.**

1.  Inspect the existing Visit Plan code, related
    routes/controllers/services/models/helpers, migrations, database
    connections, classification rules, contact-recording queries, and
    tests.
2.  Summarize the current implementation and identify which requirements
    above are already implemented, missing, conflicting, or unsupported
    by the current schema.
3.  Produce a proposed implementation plan with files to change,
    data/database changes, rule-evaluation order, query approach, tests,
    and risks.
4.  Ask focused clarification questions for unresolved business rules
    before implementation. Do not guess.
5.  Wait for approval before changing files or running destructive
    database operations.
6.  After approval, implement only the approved scope and add/update
    tests for precedence, due dates, same-day rescheduling, fallback to
    `current_category`, and parameterized rules.
7.  Report the changed files, migrations/configuration needed, tests
    executed and results, and any remaining questions.

## 12. Questions that must be resolved before implementation

Do not guess the answers to these questions. Inspect the code first,
then ask only the questions that remain unresolved:

1.  **NP0 and NP1:** Is `no_of_non_payments = 0` alone sufficient for
    `np_0` regardless of `dpd_bucket`? Is `np_1` strictly
    `no_of_non_payments = 1` plus `DPD 31-60`?
2.  **Uncovered DPD buckets:** What should the base classification be
    for `DPD 0`, `DPD 121-150`, and higher buckets that do not match the
    listed rules?
3.  **FDD/NS and due next actions:** Does a due `PROMISE TO PAY`,
    `FOR SKIPTRACE`, or `FOR SERVICE OF COLLECTION NOTICE` override
    `fdd_ns`, subject to repossession and force-priority precedence?
4.  **`FOR REPO` approval:** Which existing field/table identifies that
    a higher-up approved the repossession request? If none exists,
    should this path remain unimplemented for now?
5.  **Due date and schedule time:** Is `next_action_date` a `DATE` or
    `DATETIME`, and should a same-day action appear only once its
    scheduled time has arrived, or merely because its calendar date is
    today?
6.  **Monthly archive:** Is there an existing archive flag/table/job, or
    should previous-month unvisited accounts simply be excluded from the
    current Visit Plan query?
7.  **Separate database:** Does "separate database" mean a separate
    database/schema on the same server or a separate server/connection?
    Confirm deployment and credential ownership before implementation.

## 13. Acceptance criteria

-   Classification follows the approved precedence order.
-   `for_repossession` cannot be overridden by `force_prioritized`.
-   `force_prioritized` is based on `is_force_prioritized = true`,
    subject to precedence.
-   Due next action plan classifications require a contact recording and
    a due `next_action_date`.
-   The listed general next action plans fall back to
    `current_category`.
-   New accounts without a contact recording are scheduled according to
    the approved due-date and monthly rollover behavior.
-   A new afternoon next action can appear after a morning contact
    recording on the same day.
-   Each account appears in only one final classification.
-   Rules are parameterized and maintained in the approved separate
    rules database.
-   Response keys use `snake_case`.
-   Tests cover the agreed edge cases and no unrelated integrations are
    introduced.
