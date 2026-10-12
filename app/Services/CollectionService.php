<?php

namespace App\Services;

class CollectionService
{
    /**
     * Get demo collection accounts list.
     */
    public function getCollections(array $filters = []): array
    {
        $collections = [
            [
                'id' => 1,
                'account_id' => 101,
                'account_number' => 'ACC-2026-00101',
                'customer_name' => 'Juan Dela Cruz',
                'contact_number' => '0917-123-4567',
                'asset' => 'Honda Click 125i (2024)',
                'plate_number' => 'ND 1234',
                'address' => [
                    'street' => '123 Rizal St.',
                    'barangay' => 'Barangay Central',
                    'city' => 'Quezon City',
                    'province' => 'Metro Manila',
                    'psgc_code' => '137404000',
                    'latitude' => 14.6507,
                    'longitude' => 121.0494,
                ],
                'monthly_amortization' => 3850.00,
                'past_due_balance' => 11550.00,
                'outstanding_balance' => 65450.00,
                'days_past_due' => 45,
                'dpd_bucket' => '30-59 days',
                'no_of_non_payments' => 3,
                'last_payment_date' => '2026-08-15',
                'due_date' => '2026-09-15',
                'payment_status' => 'Delinquent',
                'collection_status' => 'Pending Visit',
                'priority' => 'High',
                'is_force_prioritized' => true,
                'assigned_collector' => 'Mark Santos',
            ],
            [
                'id' => 2,
                'account_id' => 102,
                'account_number' => 'ACC-2026-00102',
                'customer_name' => 'Maria Clara Santos',
                'contact_number' => '0928-555-6789',
                'asset' => 'Yamaha NMAX 155 (2025)',
                'plate_number' => 'NC 5678',
                'address' => [
                    'street' => '45 Mabini Avenue',
                    'barangay' => 'San Antonio',
                    'city' => 'Pasig City',
                    'province' => 'Metro Manila',
                    'psgc_code' => '137403000',
                    'latitude' => 14.5764,
                    'longitude' => 121.0851,
                ],
                'monthly_amortization' => 5200.00,
                'past_due_balance' => 5200.00,
                'outstanding_balance' => 88400.00,
                'days_past_due' => 22,
                'dpd_bucket' => '1-29 days',
                'no_of_non_payments' => 1,
                'last_payment_date' => '2026-09-05',
                'due_date' => '2026-09-28',
                'payment_status' => 'Grace Period',
                'collection_status' => 'Promised to Pay',
                'priority' => 'Medium',
                'is_force_prioritized' => false,
                'assigned_collector' => 'Mark Santos',
            ],
            [
                'id' => 3,
                'account_id' => 103,
                'account_number' => 'ACC-2026-00103',
                'customer_name' => 'Roberto Bautista',
                'contact_number' => '0919-888-2345',
                'asset' => 'Suzuki Raider 150 Fi',
                'plate_number' => 'NA 9012',
                'address' => [
                    'street' => '88 Bonifacio Rd.',
                    'barangay' => 'Bagong Silang',
                    'city' => 'Caloocan City',
                    'province' => 'Metro Manila',
                    'psgc_code' => '137501000',
                    'latitude' => 14.7566,
                    'longitude' => 121.0450,
                ],
                'monthly_amortization' => 4100.00,
                'past_due_balance' => 16400.00,
                'outstanding_balance' => 49200.00,
                'days_past_due' => 92,
                'dpd_bucket' => '90+ days',
                'no_of_non_payments' => 4,
                'last_payment_date' => '2026-06-30',
                'due_date' => '2026-07-30',
                'payment_status' => 'Critical Default',
                'collection_status' => 'For Repossession Endorsement',
                'priority' => 'Critical',
                'is_force_prioritized' => true,
                'assigned_collector' => 'Mark Santos',
            ],
            [
                'id' => 4,
                'account_id' => 104,
                'account_number' => 'ACC-2026-00104',
                'customer_name' => 'Grace Villanueva',
                'contact_number' => '0935-777-4321',
                'asset' => 'Honda Beat 110 (2024)',
                'plate_number' => 'NE 3456',
                'address' => [
                    'street' => '12 MacArthur Highway',
                    'barangay' => 'Potrero',
                    'city' => 'Malabon City',
                    'province' => 'Metro Manila',
                    'psgc_code' => '137502000',
                    'latitude' => 14.6644,
                    'longitude' => 120.9789,
                ],
                'monthly_amortization' => 2950.00,
                'past_due_balance' => 5900.00,
                'outstanding_balance' => 41300.00,
                'days_past_due' => 38,
                'dpd_bucket' => '30-59 days',
                'no_of_non_payments' => 2,
                'last_payment_date' => '2026-08-20',
                'due_date' => '2026-09-20',
                'payment_status' => 'Delinquent',
                'collection_status' => 'Visited',
                'priority' => 'Medium',
                'is_force_prioritized' => false,
                'assigned_collector' => 'Mark Santos',
            ],
            [
                'id' => 5,
                'account_id' => 105,
                'account_number' => 'ACC-2026-00105',
                'customer_name' => 'Eduardo Ramos',
                'contact_number' => '0908-333-8899',
                'asset' => 'Kawasaki Barako II',
                'plate_number' => 'NF 7890',
                'address' => [
                    'street' => '74 Quirino Avenue',
                    'barangay' => 'Tambo',
                    'city' => 'Parañaque City',
                    'province' => 'Metro Manila',
                    'psgc_code' => '137604000',
                    'latitude' => 14.5097,
                    'longitude' => 120.9950,
                ],
                'monthly_amortization' => 3400.00,
                'past_due_balance' => 23800.00,
                'outstanding_balance' => 37400.00,
                'days_past_due' => 125,
                'dpd_bucket' => '90+ days',
                'no_of_non_payments' => 7,
                'last_payment_date' => '2026-05-10',
                'due_date' => '2026-06-10',
                'payment_status' => 'Severe Delinquency',
                'collection_status' => 'Unreachable',
                'priority' => 'Critical',
                'is_force_prioritized' => true,
                'assigned_collector' => 'Mark Santos',
            ],
        ];

        // Apply filters if provided
        if (!empty($filters['status'])) {
            $collections = array_values(array_filter($collections, fn($item) => strcasecmp($item['collection_status'], $filters['status']) === 0));
        }

        if (!empty($filters['dpd_bucket'])) {
            $collections = array_values(array_filter($collections, fn($item) => strcasecmp($item['dpd_bucket'], $filters['dpd_bucket']) === 0));
        }

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $collections = array_values(array_filter($collections, function ($item) use ($search) {
                return str_contains(strtolower($item['customer_name']), $search) ||
                    str_contains(strtolower($item['account_number']), $search) ||
                    str_contains(strtolower($item['asset']), $search);
            }));
        }

        return [
            'total_count' => count($collections),
            'summary' => [
                'total_past_due' => array_sum(array_column($collections, 'past_due_balance')),
                'total_outstanding' => array_sum(array_column($collections, 'outstanding_balance')),
                'pending_visits' => count(array_filter($collections, fn($i) => $i['collection_status'] === 'Pending Visit')),
                'critical_accounts' => count(array_filter($collections, fn($i) => $i['priority'] === 'Critical')),
            ],
            'items' => $collections,
        ];
    }

    /**
     * Get detailed collection case by id.
     */
    public function getCollectionDetail(int $id): array
    {
        return [
            'id' => $id,
            'account_id' => 100 + $id,
            'account_number' => sprintf('ACC-2026-%05d', 100 + $id),
            'customer_name' => 'Juan Dela Cruz',
            'contact_number' => '0917-123-4567',
            'alternate_contact' => '0922-987-6543',
            'email' => 'juan.delacruz@example.com',
            'asset_details' => [
                'make_model' => 'Honda Click 125i (2024)',
                'plate_number' => 'ND 1234',
                'engine_number' => 'JF68E-987654',
                'chassis_number' => 'MH1JF6810PK-12345',
                'color' => 'Pearl Horizon White',
            ],
            'address' => [
                'residence_address' => '123 Rizal St., Barangay Central, Quezon City',
                'landmark' => 'Near Central Elementary School, blue gate',
                'ownership' => 'Living with Parents',
                'years_of_stay' => 8,
                'latitude' => 14.6507,
                'longitude' => 121.0494,
            ],
            'loan_summary' => [
                'loan_type' => 'Motorcycle Financing',
                'original_principal' => 95000.00,
                'loan_term_months' => 36,
                'terms_completed' => 17,
                'remaining_terms' => 19,
                'monthly_amortization' => 3850.00,
                'outstanding_balance' => 65450.00,
                'past_due_balance' => 11550.00,
                'penalty_charges' => 950.00,
                'total_amount_due' => 12500.00,
                'days_past_due' => 45,
                'dpd_bucket' => '30-59 days',
                'last_payment_date' => '2026-08-15',
                'last_payment_amount' => 3850.00,
            ],
            'collection_info' => [
                'status' => 'Pending Visit',
                'priority' => 'High',
                'is_force_prioritized' => true,
                'assigned_collector_id' => 12,
                'assigned_collector_name' => 'Mark Santos',
                'last_contact_date' => '2026-09-28',
                'last_action_taken' => 'Field Visit - Borrower away at work',
                'latest_promise_to_pay' => [
                    'amount' => 7700.00,
                    'date' => '2026-10-15',
                    'status' => 'Pending',
                ],
            ],
            'recent_history' => [
                [
                    'date' => '2026-09-28',
                    'type' => 'Field Visit',
                    'result' => 'Spoke with sister, promised borrower will call back tonight',
                    'collector' => 'Mark Santos',
                ],
                [
                    'date' => '2026-09-18',
                    'type' => 'Phone Call',
                    'result' => 'Customer acknowledged overdue amount; cited hospitalization expenses',
                    'collector' => 'Mark Santos',
                ],
                [
                    'date' => '2026-08-15',
                    'type' => 'Payment Receipt',
                    'result' => 'Payment of ₱3,850.00 received via GCash (Ref # 8392109)',
                    'collector' => 'System',
                ],
            ],
        ];
    }

    /**
     * Record a collection action / visit report.
     */
    public function recordAction(int $id, array $data): array
    {
        return [
            'action_id' => rand(1000, 9999),
            'collection_id' => $id,
            'account_number' => sprintf('ACC-2026-%05d', 100 + $id),
            'action_taken_type' => $data['action_type'] ?? 'Personal Contact / Field Visit',
            'follow_up_mode' => $data['follow_up_mode'] ?? 'Field Visit',
            'reason_for_default' => $data['reason_for_default'] ?? 'Financial Strain / Medical Expense',
            'next_action_plan' => $data['next_action_plan'] ?? 'Follow up on Promise to Pay date',
            'remarks' => $data['remarks'] ?? 'Borrower visited at residence; discussed payment schedule options.',
            'geo_location' => [
                'latitude' => $data['latitude'] ?? 14.6507,
                'longitude' => $data['longitude'] ?? 121.0494,
                'captured_at' => now()->toIso8601String(),
            ],
            'recorded_by' => 'Demo Field Collector',
            'recorded_at' => now()->toIso8601String(),
            'sync_status' => 'Synced',
        ];
    }

    /**
     * Record a field collection payment receipt.
     */
    public function recordReceipt(int $id, array $data): array
    {
        $amount = (float)($data['amount_paid'] ?? 3850.00);
        $orNumber = $data['or_number'] ?? ('BMI-OR-' . rand(100000, 999999));

        return [
            'receipt_id' => rand(5000, 8999),
            'collection_id' => $id,
            'account_number' => sprintf('ACC-2026-%05d', 100 + $id),
            'or_number' => $orNumber,
            'amount_paid' => $amount,
            'payment_method' => $data['payment_method'] ?? 'Cash',
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'acknowledgement_code' => strtoupper(substr(md5(uniqid()), 0, 10)),
            'issued_by' => 'Demo Field Collector',
            'status' => 'Verified',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
