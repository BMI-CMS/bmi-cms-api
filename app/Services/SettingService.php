<?php

namespace App\Services;

use App\Exceptions\ClassificationRulesException;
use App\Models\AccountClassification;
use App\Models\AccountClassificationRule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Application settings that are kept in the database. Today that is the account classification
 * rules stored in account_classification_rules (docs/general-business-rules.md, sections 4-6, 10):
 * it loads and validates the rules and classifies accounts with them.
 *
 * Rules are evaluated in ascending `precedence`; the first rule whose conditions all match
 * decides the classification. The DPD bucket is the primary reference of the seeded rules; the
 * number of non-payments (`np`) is available to rules but none of the seeded rules uses it. A rule
 * with a null `classification` means "use the label saved in the recording's current_category" (the
 * fallback next action plans); when that category is missing or unusable, evaluation simply
 * continues, so the base rules decide.
 *
 * Missing or invalid rules are never replaced by defaults: they are logged and raise a
 * ClassificationRulesException.
 */
class SettingService
{
    /** Fields a condition may use. `np` is the number of non-payments converted from the stored text. */
    private const FIELDS = ['np', 'dpd_bucket', 'payment_status', 'is_force_prioritized', 'next_action_plan'];

    private const OPERATORS = ['=', '!=', '>', '>=', '<', '<=', 'in', 'not_in'];

    private const ORDERING_OPERATORS = ['>', '>=', '<', '<='];

    /** Operators that restrict a classification (used to keep a recorded category from bypassing them). */
    private const EXCLUSION_OPERATORS = ['!=', 'not_in'];

    /** How many account numbers one "could not be classified" warning lists. */
    private const LOGGED_ACCOUNTS = 50;

    /** @var array<int, array{id: int, label: ?string, conditions: array}>|null */
    private ?array $rules = null;

    /** @var array<string, array{parent: string, path: array<int, string>}>|null */
    private ?array $classifications = null;

    /**
     * Groups the accounts the way the assigned-accounts response expects:
     * [parent => [group => [accounts]]] with the existing property names; empty groups are absent.
     * Accounts that no rule classifies are left out and reported in one warning.
     */
    public function classify(array $accounts): array
    {
        $this->rules();

        $groups = [];
        $unclassified = [];

        foreach ($accounts as $account) {
            $label = $this->classifyAccount($account);

            if ($label === null) {
                $unclassified[] = $account['account_number'] ?? $account['accounts_id'] ?? '?';
                continue;
            }

            $node = &$groups;
            foreach ($this->classifications()[$label]['path'] as $key) {
                $node = &$node[$key];
            }
            $node[] = $account;
            unset($node);
        }

        if ($unclassified !== []) {
            Log::warning('Visit Plan accounts could not be classified: no active classification rule matches them.', [
                'count' => count($unclassified),
                'account_numbers' => array_slice($unclassified, 0, self::LOGGED_ACCOUNTS),
            ]);
        }

        return $groups;
    }

    /**
     * The classification label of one account, or null when no rule matches.
     */
    public function classifyAccount(array $account): ?string
    {
        $facts = $this->facts($account);

        foreach ($this->rules() as $rule) {
            if (!$this->matches($rule['conditions'], $facts)) {
                continue;
            }

            if ($rule['label'] !== null) {
                return $rule['label'];
            }

            $recorded = $this->recordedCategory($account, $facts);
            if ($recorded !== null) {
                return $recorded;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------ evaluation

    private function facts(array $account): array
    {
        return [
            'np' => $this->nonPayments($account['no_of_non_payments'] ?? null),
            'dpd_bucket' => $account['dpd_bucket'] ?? null,
            'payment_status' => $account['payment_status'] ?? null,
            'is_force_prioritized' => (bool) ($account['is_force_prioritized'] ?? false),
            'next_action_plan' => $account['next_action_plan'] ?? null,
        ];
    }

    /**
     * "UPDATED" = 0, "LAST PAID 1 MONTH AGO" = 1, "LAST PAID 3 MONTHS AGO" = 3, … Anything else
     * is unrecognised (null): it can never satisfy a condition on `np`.
     */
    private function nonPayments(mixed $text): ?int
    {
        if (!is_string($text)) {
            return null;
        }

        if ($text === 'UPDATED') {
            return 0;
        }

        return preg_match('/^LAST PAID (\d+) MONTHS? AGO$/', $text, $matches) ? (int) $matches[1] : null;
    }

    private function matches(array $conditions, array $facts): bool
    {
        foreach ($conditions as $condition) {
            if (!$this->holds($condition, $facts[$condition['field']])) {
                return false;
            }
        }

        return true;
    }

    private function holds(array $condition, mixed $actual): bool
    {
        $expected = $condition['value'];

        return match ($condition['operator']) {
            '=' => $actual !== null && $actual === $expected,
            '!=' => $actual !== $expected,
            '>' => $actual !== null && $actual > $expected,
            '>=' => $actual !== null && $actual >= $expected,
            '<' => $actual !== null && $actual < $expected,
            '<=' => $actual !== null && $actual <= $expected,
            'in' => $actual !== null && in_array($actual, $expected, true),
            'not_in' => $actual === null || !in_array($actual, $expected, true),
        };
    }

    /**
     * The label saved on the recording, if it is a classification we have a rule for and the account
     * does not violate that classification's exclusions (so a recorded "For Repossession" can never
     * apply to an FIRST DUE DATE / NON-STARTER account).
     */
    private function recordedCategory(array $account, array $facts): ?string
    {
        // The assigned-accounts entry carries the latest recording in activity_records.
        $category = $account['current_category'] ?? $account['activity_records'][0]['current_category'] ?? null;

        if (!is_string($category) || $category === '') {
            return null;
        }

        $rules = array_filter($this->rules(), fn (array $rule) => $rule['label'] === $category);

        if ($rules === []) {
            return null;
        }

        foreach ($rules as $rule) {
            $exclusions = array_filter($rule['conditions'], fn (array $condition) => in_array($condition['operator'], self::EXCLUSION_OPERATORS, true));

            if (!$this->matches($exclusions, $facts)) {
                return null;
            }
        }

        return $category;
    }

    // ------------------------------------------------------------- the classifications

    /**
     * The active classification labels, keyed by label, with their parent and response path.
     *
     * @return array<string, array{parent: string, path: array<int, string>}>
     */
    private function classifications(): array
    {
        if ($this->classifications !== null) {
            return $this->classifications;
        }

        try {
            $rows = AccountClassification::query()
                ->where('is_active', true)
                ->get();
        } catch (QueryException $e) {
            $this->fail('The classifications could not be read from account_classifications (is the migration run?): ' . $e->getMessage());
        }

        if ($rows->isEmpty()) {
            $this->fail('There are no active rows in account_classifications.');
        }

        $classifications = [];

        foreach ($rows as $row) {
            if (!is_array($row->response_path) || count($row->response_path) < 2) {
                $this->fail("Classification '{$row->label}' has no valid response path.");
            }

            $classifications[$row->label] = ['parent' => $row->parent, 'path' => $row->response_path];
        }

        return $this->classifications = $classifications;
    }

    // ------------------------------------------------------------------- the rules

    /**
     * @return array<int, array{id: int, label: ?string, conditions: array}>
     */
    private function rules(): array
    {
        if ($this->rules !== null) {
            return $this->rules;
        }

        try {
            $rows = AccountClassificationRule::query()
                ->where('is_active', true)
                ->orderBy('precedence')
                ->orderBy('id')
                ->get();
        } catch (QueryException $e) {
            $this->fail('The rules could not be read from account_classification_rules (is the migration run?): ' . $e->getMessage());
        }

        if ($rows->isEmpty()) {
            $this->fail('There are no active rows in account_classification_rules.');
        }

        $rules = [];
        $ruleByPrecedence = [];

        foreach ($rows as $row) {
            $rules[] = $this->validated($row);

            if (isset($ruleByPrecedence[$row->precedence])) {
                $this->fail("Rules #{$ruleByPrecedence[$row->precedence]} and #{$row->id} share the same precedence ({$row->precedence}); the order would be ambiguous.");
            }
            $ruleByPrecedence[$row->precedence] = $row->id;
        }

        return $this->rules = $rules;
    }

    /**
     * @return array{id: int, label: ?string, conditions: array}
     */
    private function validated(AccountClassificationRule $rule): array
    {
        $id = $rule->id;

        if ($rule->precedence === null) {
            $this->fail("Rule #{$id} has no precedence.");
        }

        $label = $rule->classification;

        if ($label !== null) {
            $classification = $this->classifications()[$label] ?? null;

            if ($classification === null) {
                $this->fail("Rule #{$id} has an unknown classification '{$label}'.");
            }

            if (strtolower((string) $rule->parent) !== $classification['parent']) {
                $this->fail("Rule #{$id}: parent '{$rule->parent}' does not match classification '{$label}'.");
            }
        }

        $conditions = $rule->conditions;

        if (!is_array($conditions)) {
            $this->fail("Rule #{$id} has no conditions.");
        }

        if (!isset($conditions['all']) || !is_array($conditions['all']) || $conditions['all'] === [] || !array_is_list($conditions['all'])) {
            $this->fail("Rule #{$id}: conditions need a non-empty 'all' list.");
        }

        return [
            'id' => $id,
            'label' => $label,
            'conditions' => array_map(fn ($condition) => $this->validatedCondition($id, $condition), $conditions['all']),
        ];
    }

    /**
     * @return array{field: string, operator: string, value: mixed}
     */
    private function validatedCondition(int $ruleId, mixed $condition): array
    {
        $field = is_array($condition) ? ($condition['field'] ?? null) : null;
        $operator = is_array($condition) ? ($condition['operator'] ?? null) : null;

        if (!is_string($field) || $field === '') {
            $this->fail("Rule #{$ruleId} has a condition without a field.");
        }

        if (!in_array($field, self::FIELDS, true)) {
            $this->fail("Rule #{$ruleId} uses an unknown field '{$field}'.");
        }

        if (!is_string($operator) || !in_array($operator, self::OPERATORS, true)) {
            $this->fail("Rule #{$ruleId} uses an unknown operator '" . (is_scalar($operator) ? $operator : gettype($operator)) . "' on '{$field}'.");
        }

        if (!array_key_exists('value', $condition)) {
            $this->fail("Rule #{$ruleId}: the condition on '{$field}' has no value.");
        }

        if (in_array($operator, self::ORDERING_OPERATORS, true) && $field !== 'np') {
            $this->fail("Rule #{$ruleId}: operator '{$operator}' can only be used on 'np'.");
        }

        $isList = in_array($operator, ['in', 'not_in'], true);
        $value = $condition['value'];

        if ($isList) {
            if (!is_array($value) || $value === [] || !array_is_list($value)) {
                $this->fail("Rule #{$ruleId}: '{$operator}' on '{$field}' needs a non-empty list of values.");
            }

            return ['field' => $field, 'operator' => $operator, 'value' => array_map(fn ($item) => $this->validatedValue($ruleId, $field, $item), $value)];
        }

        if (is_array($value)) {
            $this->fail("Rule #{$ruleId}: '{$operator}' on '{$field}' needs a single value, not a list.");
        }

        return ['field' => $field, 'operator' => $operator, 'value' => $this->validatedValue($ruleId, $field, $value)];
    }

    private function validatedValue(int $ruleId, string $field, mixed $value): mixed
    {
        return match ($field) {
            'np' => is_int($value) || (is_string($value) && ctype_digit($value))
                ? (int) $value
                : $this->fail("Rule #{$ruleId}: 'np' must be compared with a whole number."),
            'is_force_prioritized' => is_bool($value)
                ? $value
                : $this->fail("Rule #{$ruleId}: 'is_force_prioritized' must be compared with true or false."),
            default => is_string($value)
                ? $value
                : $this->fail("Rule #{$ruleId}: '{$field}' must be compared with text."),
        };
    }

    /**
     * Logs the underlying issue and raises the API-safe exception.
     */
    private function fail(string $details): never
    {
        Log::error('Account classification rules are invalid: ' . $details);

        throw new ClassificationRulesException($details);
    }
}
