<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FecCustomerEvaluation;
use Carbon\Carbon;

class FecCustomerEvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        FecCustomerEvaluation::create([
            'fec_accounts_id' => 1,
            'table_a_section_1' => [true, true, true, true, true, true, true, true,  true, true],
            'table_a_section_2' => [
                'model' => '12333123',
                'brand' => 'HONDA',
                'unit' => 'NEW',
                'color' => 'BLACK',
                'cc_remarks' => 'testtestet',
                'motorcycle_unit_seen' => 'customer residence',
            ],
            'table_a_section_3' => "every 15th of the month",
            'table_a_section_4' => "YES - Customer has complaint",
            'table_b_section_5' => [
                'rented_or_present_address' => true,
                'rented_or_present_address_remarks' => "remarks",
                'residing_with_relatives' => true,
                'residing_with_relatives_remarks' => "remarks",
                'length_of_stay' => "5 years",
                'length_of_stay_remarks' => "remarks",
                'regular_employee' => true,
                'regular_employee_remarks' => "remarks",
                'self_employeed' => true,
                'self_employeed_remarks' => "remarks",
                'self_employeed_small_business' => true,
                'self_employeed_small_business_remarks' => "remarks",
                'employeed_self_employable' =>  true,
                'employeed_self_employable_remarks' => "remarks",
                'is_driver' =>  true,
                'is_driver_employable_remarks' => "remarks",
                'has_self_sole_prorietor' =>  true,
                'has_self_sole_prorietor_remarks' => "remarks",
                'character' => "testtttttttttttttttttttttttttttt",
                'capacity' => "testtttttttttttttttttttttttttttt",
            ],
            'table_b_section_6' => [
                'finding' =>  'WITH/NO/DEFFER FINDING',
                'details' => 'INDIVIDUAL',
            ],
            'fec_form' => 'docu',
            'remarks'  => '',
            'visit_date' => $now->copy()->subDays(),
            'submit_date' => $now
        ]);

        FecCustomerEvaluation::create([
            'fec_accounts_id' => 2,
            'table_a_section_1' => [true, true, true, true, true, true, true, true,  true, true],
            'table_a_section_2' => [
                'model' => '12333123',
                'brand' => 'HONDA',
                'unit' => 'NEW',
                'color' => 'BLACK',
                'cc_remarks' => 'testtestet',
                'motorcycle_unit_seen' => 'customer residence',
            ],
            'table_a_section_3' => "every 15th of the month",
            'table_a_section_4' => "YES - Customer has complaint",
            'table_b_section_5' => [
                'rented_or_present_address' => true,
                'rented_or_present_address_remarks' => "remarks",
                'residing_with_relatives' => true,
                'residing_with_relatives_remarks' => "remarks",
                'length_of_stay' => "5 years",
                'length_of_stay_remarks' => "remarks",
                'regular_employee' => true,
                'regular_employee_remarks' => "remarks",
                'self_employeed' => true,
                'self_employeed_remarks' => "remarks",
                'self_employeed_small_business' => true,
                'self_employeed_small_business_remarks' => "remarks",
                'employeed_self_employable' =>  true,
                'employeed_self_employable_remarks' => "remarks",
                'is_driver' =>  true,
                'is_driver_employable_remarks' => "remarks",
                'has_self_sole_prorietor' =>  true,
                'has_self_sole_prorietor_remarks' => "remarks",
                'character' => "testtttttttttttttttttttttttttttt",
                'capacity' => "testtttttttttttttttttttttttttttt",
            ],
            'table_b_section_6' => [
                'finding' => "With/no/DEFFER FINDING",
                'details' => 'INDIVIDUAL',
            ],
            'fec_form' => 'docu',
            'remarks'  => '',
            'visit_date' => $now->copy()->subDays(),
            'submit_date' => $now
        ]);


        FecCustomerEvaluation::create([
            'fec_accounts_id' => 3,
            'table_a_section_1' => [true, true, true, true, true, true, true, true,  true, true],
            'table_a_section_2' => [
                'model' => '12333123',
                'brand' => 'HONDA',
                'unit' => 'NEW',
                'color' => 'BLACK',
                'cc_remarks' => 'testtestet',
                'motorcycle_unit_seen' => 'customer residence',
            ],
            'table_a_section_3' => "every 15th of the month",
            'table_a_section_4' => "YES - Customer has complaint",
            'table_b_section_5' => [
                'rented_or_present_address' => true,
                'rented_or_present_address_remarks' => "remarks",
                'residing_with_relatives' => true,
                'residing_with_relatives_remarks' => "remarks",
                'length_of_stay' => "5 years",
                'length_of_stay_remarks' => "remarks",
                'regular_employee' => true,
                'regular_employee_remarks' => "remarks",
                'self_employeed' => true,
                'self_employeed_remarks' => "remarks",
                'self_employeed_small_business' => true,
                'self_employeed_small_business_remarks' => "remarks",
                'employeed_self_employable' =>  true,
                'employeed_self_employable_remarks' => "remarks",
                'is_driver' =>  true,
                'is_driver_employable_remarks' => "remarks",
                'has_self_sole_prorietor' =>  true,
                'has_self_sole_prorietor_remarks' => "remarks",
                'character' => "testtttttttttttttttttttttttttttt",
                'capacity' => "testtttttttttttttttttttttttttttt",
            ],
            'table_b_section_6' => [
                'finding' => "With/no/DEFFER FINDING",
                'details' => 'INDIVIDUAL',
            ],
            'fec_form' => 'docu',
            'remarks'  => '',
            'visit_date' => $now->copy()->subDays(),
            'submit_date' => $now
        ]);
    }
}
