<?php

namespace App\Services;

class RepossessionService
{
    /**
     * Get list of accounts endorsed or queued for repossession.
     */
    public function getRepossessionList(array $filters = []): array
    {
        $records = [
            [
                'repossession_id' => 1,
                'account_id' => 103,
                'account_number' => 'ACC-2026-00103',
                'customer_name' => 'Roberto Bautista',
                'contact_number' => '0919-888-2345',
                'asset' => 'Suzuki Raider 150 Fi (2023)',
                'plate_number' => 'NA 9012',
                'engine_number' => 'F401-192837',
                'chassis_number' => 'MH8BG41A-981273',
                'days_past_due' => 92,
                'past_due_balance' => 16400.00,
                'outstanding_balance' => 49200.00,
                'status' => 'Pending First Level Approval',
                'approval_stage' => 'Level 1 of 3',
                'stockyard_designated' => 'Caloocan Central Stockyard',
                'endorsement_date' => '2026-10-02',
            ],
            [
                'repossession_id' => 2,
                'account_id' => 105,
                'account_number' => 'ACC-2026-00105',
                'customer_name' => 'Eduardo Ramos',
                'contact_number' => '0908-333-8899',
                'asset' => 'Kawasaki Barako II (2023)',
                'plate_number' => 'NF 7890',
                'engine_number' => 'BC175AE-88912',
                'chassis_number' => 'MH4BC175-77621',
                'days_past_due' => 125,
                'past_due_balance' => 23800.00,
                'outstanding_balance' => 37400.00,
                'status' => 'Approved for Pull-out',
                'approval_stage' => 'Fully Approved',
                'stockyard_designated' => 'Parañaque Logistics Hub',
                'endorsement_date' => '2026-09-20',
            ],
            [
                'repossession_id' => 3,
                'account_id' => 108,
                'account_number' => 'ACC-2026-00108',
                'customer_name' => 'Fernando Alcantara',
                'contact_number' => '0927-444-5566',
                'asset' => 'Yamaha Aerox 155 S (2024)',
                'plate_number' => 'NG 1029',
                'engine_number' => 'G3N9E-382910',
                'chassis_number' => 'MH3SG531-10293',
                'days_past_due' => 140,
                'past_due_balance' => 28000.00,
                'outstanding_balance' => 72000.00,
                'status' => 'In Stockyard',
                'approval_stage' => 'Completed - Stored',
                'stockyard_designated' => 'Quezon City Main Depot',
                'endorsement_date' => '2026-09-05',
            ],
        ];

        if (!empty($filters['status'])) {
            $records = array_values(array_filter($records, fn($r) => strcasecmp($r['status'], $filters['status']) === 0));
        }

        return [
            'total_repossession_cases' => count($records),
            'summary' => [
                'pending_approval' => count(array_filter($records, fn($r) => str_contains($r['status'], 'Pending'))),
                'approved_pullout' => count(array_filter($records, fn($r) => $r['status'] === 'Approved for Pull-out')),
                'in_stockyard' => count(array_filter($records, fn($r) => $r['status'] === 'In Stockyard')),
            ],
            'items' => $records,
        ];
    }

    /**
     * Get detailed dossier for a repossession case.
     */
    public function getRepossessionDetail(int $id): array
    {
        return [
            'repossession_id' => $id,
            'account_id' => 100 + $id,
            'account_number' => sprintf('ACC-2026-%05d', 100 + $id),
            'customer_name' => 'Roberto Bautista',
            'contact_number' => '0919-888-2345',
            'address' => '88 Bonifacio Rd., Barangay Bagong Silang, Caloocan City',
            'asset_details' => [
                'make_model' => 'Suzuki Raider 150 Fi',
                'year_model' => 2023,
                'color' => 'Titan Black / Metallic Mat Stellar Blue',
                'plate_number' => 'NA 9012',
                'engine_number' => 'F401-192837',
                'chassis_number' => 'MH8BG41A-981273',
                'unit_condition' => 'Good Condition, Minor scratches on right side fairing',
            ],
            'borrower_evaluation' => [
                'customer_capacity' => 'Low - Lost primary source of income',
                'customer_character' => 'Evasive - Avoids scheduled field appointments',
                'default_reason' => 'Business closure / loss of livelihood',
            ],
            'approval_workflow' => [
                'first_level' => [
                    'approved' => true,
                    'approved_by' => 'Team Leader Ronald Cruz',
                    'reviewed_at' => '2026-10-03T11:20:00Z',
                    'remarks' => 'Endorsement verified. Account is 92 DPD with consecutive missed payments.',
                ],
                'second_level' => [
                    'approved' => false,
                    'approved_by' => 'Area Manager Antonio Luna',
                    'reviewed_at' => null,
                    'remarks' => 'Pending review of borrower final response.',
                ],
                'third_level' => [
                    'approved' => false,
                    'approved_by' => 'Department Head Maria Santos',
                    'reviewed_at' => null,
                    'remarks' => null,
                ],
            ],
            'stockyard_info' => [
                'assigned_stockyard' => 'Caloocan Central Stockyard',
                'yard_address' => '100 Samson Road, Caloocan City',
                'towing_partner' => 'Metro Safe Towing Services',
                'towing_contact' => '0917-555-8822',
            ],
            'status' => 'Pending Level 2 Approval',
            'created_at' => '2026-10-02T09:15:00Z',
        ];
    }

    /**
     * Submit a new repossession endorsement request.
     */
    public function createRepossessionRequest(array $data): array
    {
        return [
            'request_id' => rand(500, 999),
            'account_id' => $data['account_id'] ?? 103,
            'unit_condition' => $data['unit_condition'] ?? 'Running condition, normal wear and tear',
            'customer_capacity' => $data['customer_capacity'] ?? 'Insolvent / Incapable of sustaining monthly dues',
            'customer_character' => $data['customer_character'] ?? 'Refuses communication after 3 field visits',
            'remarks' => $data['remarks'] ?? 'Endorsed for voluntary or legal repossession pull-out.',
            'status' => 'Pending First Level Approval',
            'first_level_approved' => false,
            'submitted_by' => 'Demo Field Collector',
            'submitted_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Update repossession pull-out / stockyard status.
     */
    public function updateRepossessionStatus(int $id, array $data): array
    {
        $status = $data['status'] ?? 'Stored in Stockyard';
        $stockyard = $data['stockyard'] ?? 'Caloocan Central Stockyard';

        return [
            'repossession_id' => $id,
            'status' => $status,
            'stockyard' => $stockyard,
            'reason_for_unrepossessed' => $data['reason_for_unrepossessed'] ?? null,
            'pullout_type' => $data['surrender_type'] ?? 'Voluntary Surrender',
            'unit_received_at' => now()->toIso8601String(),
            'notes' => $data['retrieval_notes'] ?? 'Unit safely checked in and inventoried at designated yard.',
            'updated_by' => 'Demo Field Officer',
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
