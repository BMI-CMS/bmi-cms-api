<?php

namespace App\Services;

class FirstEncounterService
{
    /**
     * Get list of accounts assigned for First Encounter Checklist (FEC).
     */
    public function getFecAccounts(array $filters = []): array
    {
        $accounts = [
            [
                'fec_account_id' => 1,
                'account_number' => 'FEC-2026-0001',
                'customer_name' => 'Danilo Cortez',
                'contact_number' => '0917-889-1122',
                'asset' => 'Yamaha Mio Gear 125 (2024)',
                'plate_number' => 'NA 4589',
                'address' => 'Block 12 Lot 4, Phase 2, Mabuhay City, Dasmariñas, Cavite',
                'monthly_amortization' => 3450.00,
                'past_due_balance' => 6900.00,
                'days_past_due' => 35,
                'form_status' => 'Pending Visit',
                'is_force_prioritized' => true,
                'is_adverse_finding' => false,
                'visit_due_date' => '2026-10-12',
                'assigned_date' => '2026-10-01',
            ],
            [
                'fec_account_id' => 2,
                'account_number' => 'FEC-2026-0002',
                'customer_name' => 'Aileen Soriano',
                'contact_number' => '0920-456-7890',
                'asset' => 'Honda Beat Street 110',
                'plate_number' => 'NC 9921',
                'address' => '45 Jasmin St., Barangay San Jose, Rodriguez, Rizal',
                'monthly_amortization' => 2900.00,
                'past_due_balance' => 2900.00,
                'days_past_due' => 18,
                'form_status' => 'In Progress',
                'is_force_prioritized' => false,
                'is_adverse_finding' => false,
                'visit_due_date' => '2026-10-14',
                'assigned_date' => '2026-10-02',
            ],
            [
                'fec_account_id' => 3,
                'account_number' => 'FEC-2026-0003',
                'customer_name' => 'Rogelio Fernandez',
                'contact_number' => '0998-123-9988',
                'asset' => 'Suzuki Burgman Street 125 EX',
                'plate_number' => 'ND 3320',
                'address' => '102 Sampaguita St., Commonwealth, Quezon City',
                'monthly_amortization' => 4150.00,
                'past_due_balance' => 12450.00,
                'days_past_due' => 62,
                'form_status' => 'Adverse Finding Reported',
                'is_force_prioritized' => true,
                'is_adverse_finding' => true,
                'visit_due_date' => '2026-10-08',
                'assigned_date' => '2026-09-25',
            ],
        ];

        if (!empty($filters['status'])) {
            $accounts = array_values(array_filter($accounts, fn($a) => strcasecmp($a['form_status'], $filters['status']) === 0));
        }

        return [
            'total_fec_accounts' => count($accounts),
            'summary' => [
                'pending_visits' => count(array_filter($accounts, fn($a) => $a['form_status'] === 'Pending Visit')),
                'in_progress' => count(array_filter($accounts, fn($a) => $a['form_status'] === 'In Progress')),
                'with_adverse_findings' => count(array_filter($accounts, fn($a) => $a['is_adverse_finding'] === true)),
            ],
            'items' => $accounts,
        ];
    }

    /**
     * Get First Encounter Checklist details and evaluation template.
     */
    public function getFecAccountDetail(int $id): array
    {
        return [
            'fec_account_id' => $id,
            'account_number' => sprintf('FEC-2026-%04d', $id),
            'customer_name' => 'Danilo Cortez',
            'contact_number' => '0917-889-1122',
            'asset' => [
                'unit_description' => 'Yamaha Mio Gear 125 (2024)',
                'plate_number' => 'NA 4589',
                'engine_number' => 'E3W2E-009214',
                'chassis_number' => 'MH3SE8810PK-01923',
                'color' => 'Matte Dark Bluish Gray',
            ],
            'address' => [
                'full_address' => 'Block 12 Lot 4, Phase 2, Mabuhay City, Dasmariñas, Cavite',
                'landmark' => 'Near Mabuhay Covered Basketball Court',
                'latitude' => 14.3294,
                'longitude' => 120.9367,
            ],
            'form_status' => 'Pending Visit',
            'checklist_template' => [
                'table_a' => [
                    'title' => 'Customer & Unit Verification',
                    'sections' => [
                        [
                            'section_id' => 'section_1',
                            'title' => 'Borrower Verification',
                            'fields' => ['Identity Confirmed', 'Residency Confirmed', 'House Structure Quality'],
                        ],
                        [
                            'section_id' => 'section_2',
                            'title' => 'Unit & Asset Inspection',
                            'fields' => ['Unit Physically Present', 'Odometer Reading', 'Running Condition', 'Modifications Present'],
                        ],
                    ],
                ],
                'table_b' => [
                    'title' => 'Character & Background Evaluation',
                    'sections' => [
                        [
                            'section_id' => 'section_5',
                            'title' => 'Neighborhood Inquiries',
                            'fields' => ['Reputation in Neighborhood', 'Permanent Resident Duration', 'Barangay Verification'],
                        ],
                        [
                            'section_id' => 'section_6',
                            'title' => 'Adverse Findings Assessment',
                            'fields' => ['Unit Mortgaged / Pawned', 'Borrower Evading Contact', 'Asset Subleased'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Submit completed FEC customer evaluation.
     */
    public function submitEvaluation(int $id, array $data): array
    {
        return [
            'evaluation_id' => rand(100, 999),
            'fec_account_id' => $id,
            'account_number' => sprintf('FEC-2026-%04d', $id),
            'submission_status' => 'Submitted for Verification',
            'is_adverse_finding' => $data['is_adverse_finding'] ?? false,
            'table_a_summary' => [
                'residence_verified' => true,
                'unit_inspected' => true,
                'unit_condition' => $data['unit_condition'] ?? 'Good Condition - Normal Wear',
                'odometer_reading' => $data['odometer_reading'] ?? 8450,
            ],
            'table_b_summary' => [
                'neighborhood_feedback' => $data['neighborhood_feedback'] ?? 'Confirmed living at registered address',
                'risk_level' => $data['risk_level'] ?? 'Low Risk',
            ],
            'remarks' => $data['remarks'] ?? 'Borrower visited in person. Verified identity and unit inspected in running condition.',
            'geo_location' => [
                'latitude' => $data['latitude'] ?? 14.3294,
                'longitude' => $data['longitude'] ?? 120.9367,
            ],
            'evaluated_by' => 'Demo Field Officer',
            'submitted_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Log a visit attempt for FEC account.
     */
    public function logVisitAttempt(int $id, array $data): array
    {
        return [
            'visit_id' => rand(1000, 4999),
            'fec_account_id' => $id,
            'is_customer_available' => (bool)($data['is_customer_available'] ?? true),
            'next_visit_date' => $data['next_visit_date'] ?? now()->addDays(3)->toDateString(),
            'remarks' => $data['remarks'] ?? 'Visited residence; customer was available and scheduled follow-up interview.',
            'logged_by' => 'Demo Field Officer',
            'recorded_at' => now()->toIso8601String(),
        ];
    }
}
