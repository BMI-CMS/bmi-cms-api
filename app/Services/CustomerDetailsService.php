<?php

namespace App\Services;

class CustomerDetailsService
{
    /**
     * Get comprehensive customer profile for demo.
     */
    public function getCustomerDetails(int $id): array
    {
        return [
            'customer_id' => $id,
            'account_id' => 100 + $id,
            'account_number' => sprintf('ACC-2026-%05d', 100 + $id),
            'personal_info' => [
                'first_name' => 'Juan',
                'middle_name' => 'Crisostomo',
                'last_name' => 'Dela Cruz',
                'full_name' => 'Juan C. Dela Cruz',
                'birth_date' => '1990-05-14',
                'age' => 36,
                'gender' => 'Male',
                'civil_status' => 'Married',
                'nationality' => 'Filipino',
                'tin_number' => '123-456-789-000',
                'sss_number' => '34-5678901-2',
            ],
            'contact_info' => [
                'primary_mobile' => '0917-123-4567',
                'secondary_mobile' => '0922-987-6543',
                'landline' => '(02) 8923-4567',
                'email' => 'juan.delacruz@example.com',
                'social_media' => [
                    'facebook' => 'https://facebook.com/juan.delacruz.demo',
                    'viber' => '0917-123-4567',
                ],
            ],
            'residence_info' => [
                'complete_address' => '123 Rizal Street, Barangay Central, Quezon City, Metro Manila',
                'barangay' => 'Central',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'postal_code' => '1100',
                'psgc_code' => '137404000',
                'residence_type' => 'Living with Relatives',
                'years_of_residency' => 8,
                'landmark' => 'In front of Central Elementary School, Blue gate',
                'coordinates' => [
                    'latitude' => 14.6507,
                    'longitude' => 121.0494,
                ],
            ],
            'employment_info' => [
                'employer_name' => 'Mega Logistics Services Inc.',
                'position' => 'Senior Delivery Supervisor',
                'employment_status' => 'Regular / Permanent',
                'monthly_gross_income' => 32000.00,
                'years_employed' => 4,
                'office_address' => '77 EDSA cor. North Ave, Quezon City',
                'office_phone' => '(02) 8999-1234',
            ],
            'co_maker_info' => [
                'co_maker_name' => 'Maria Elena Dela Cruz',
                'relationship' => 'Spouse',
                'contact_number' => '0918-234-5678',
                'address' => '123 Rizal Street, Barangay Central, Quezon City',
                'employer_name' => 'St. Luke BPO Solutions',
                'monthly_income' => 25000.00,
            ],
            'account_status' => [
                'status' => 'Delinquent',
                'risk_rating' => 'Medium Risk',
                'force_prioritized' => true,
                'last_assessed_at' => '2026-10-01T08:00:00Z',
            ],
        ];
    }

    /**
     * Update customer contact information.
     */
    public function updateContact(int $id, array $data): array
    {
        return [
            'customer_id' => $id,
            'contact_number' => $data['contact_number'] ?? '0917-123-4567',
            'secondary_contact' => $data['secondary_contact'] ?? '0922-987-6543',
            'email' => $data['email'] ?? 'juan.delacruz@example.com',
            'social_media_account' => $data['social_media_account'] ?? 'facebook.com/juan.delacruz.demo',
            'notes' => $data['notes'] ?? 'Contact information confirmed with borrower during field visit.',
            'updated_by' => 'Demo Field Collector',
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get financial and loan repayment profile.
     */
    public function getFinancialProfile(int $id): array
    {
        return [
            'customer_id' => $id,
            'account_number' => sprintf('ACC-2026-%05d', 100 + $id),
            'loan_details' => [
                'loan_type' => 'Motorcycle Financing',
                'pn_number' => sprintf('PN-%05d-2024', 100 + $id),
                'financed_unit' => 'Honda Click 125i (2024)',
                'loan_amount' => 95000.00,
                'interest_rate_pct' => 18.5,
                'loan_term_months' => 36,
                'terms_completed' => 17,
                'terms_remaining' => 19,
                'monthly_amortization' => 3850.00,
                'due_day_of_month' => 15,
            ],
            'balance_breakdown' => [
                'outstanding_principal' => 65450.00,
                'past_due_amortization' => 11550.00,
                'accrued_penalties' => 950.00,
                'legal_fees' => 0.00,
                'total_amount_due' => 12500.00,
                'days_past_due' => 45,
                'dpd_bucket' => '30-59 days',
                'consecutive_unpaid_months' => 3,
            ],
            'payment_status' => 'Delinquent',
            'last_payment' => [
                'amount' => 3850.00,
                'date' => '2026-08-15',
                'channel' => 'GCash',
                'reference_no' => 'GCASH-98231023',
            ],
        ];
    }

    /**
     * Get customer touchpoint and interaction timeline history.
     */
    public function getInteractionHistory(int $id): array
    {
        return [
            'customer_id' => $id,
            'total_interactions' => 4,
            'timeline' => [
                [
                    'id' => 1,
                    'type' => 'Field Visit',
                    'date' => '2026-09-28 14:30:00',
                    'collector' => 'Mark Santos',
                    'action' => 'Residence Visit',
                    'notes' => 'Borrower was at work; spoke with spouse Maria Elena. Issued reminder notice.',
                    'status' => 'Completed',
                ],
                [
                    'id' => 2,
                    'type' => 'Call Recording',
                    'date' => '2026-09-18 10:15:00',
                    'collector' => 'Mark Santos',
                    'action' => 'Outbound Phone Call',
                    'notes' => 'Contacted borrower on mobile. Promised to make partial payment by end of month.',
                    'status' => 'Connected',
                ],
                [
                    'id' => 3,
                    'type' => 'SMS Notification',
                    'date' => '2026-09-16 09:00:00',
                    'collector' => 'System Automated',
                    'action' => 'Demand Notice SMS',
                    'notes' => 'Payment overdue reminder sent to primary mobile 0917-123-4567.',
                    'status' => 'Delivered',
                ],
                [
                    'id' => 4,
                    'type' => 'Payment Receipt',
                    'date' => '2026-08-15 11:20:00',
                    'collector' => 'System / Gateway',
                    'action' => 'Monthly Installment Payment',
                    'notes' => '₱3,850.00 posted successfully.',
                    'status' => 'Posted',
                ],
            ],
        ];
    }
}
