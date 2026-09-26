<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\ReceiptEncoding;

class ReceiptEncodingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $records = [
            [
                'account_id'           => 1,
                'contact_recording_id' => 1,
                'for_repossession_id' => null,
                'collection_id'        => null,
                'ar_number'            => 'AR-2026-0001',
                'ar_date'              => $now->copy()->subDays(3),
                'amount'               => 1500.00,
                'receipt_image_path'   => 'receipts/sample_ar_0001.jpg',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'account_id'           => 2,
                'contact_recording_id' => 2,
                'for_repossession_id' => null,
                'collection_id'        => null,
                'ar_number'            => 'AR-2026-0002',
                'ar_date'              => $now->copy()->subDays(2),
                'amount'               => 3250.50,
                'receipt_image_path'   => 'receipts/sample_ar_0002.jpg',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'account_id'           => 3,
                'contact_recording_id' => 3,
                'for_repossession_id' => null,
                'collection_id'        => null,
                'ar_number'            => 'AR-2026-0003',
                'ar_date'              => $now->copy()->subDay(),
                'amount'               => 5000.00,
                'receipt_image_path'   => 'receipts/sample_ar_0003.jpg',
                'created_at'           => $now,
                'updated_at'           => $now,
            ],
            [
                'account_id'           => 4,
                'contact_recording_id' => 4,
                'for_repossession_id' => null,
                'collection_id'        => null,
                'ar_number'            => 'AR-2026-0004',
                'ar_date'              => $now,
                'amount'               => 1200.75,
                'receipt_image_path'   => null, // Example of an entry without an uploaded photo yet
                'created_at'           => $now,
                'updated_at'           => $now,
            ],

            [
                'account_id'           => 5,
                'contact_recording_id' => null,
                'for_repossession_id' => 1,
                'collection_id'        => null,
                'ar_number'            => 'AR-2026-0004',
                'ar_date'              => $now,
                'amount'               => 1200.75,
                'receipt_image_path'   => null, // Example of an entry without an uploaded photo yet
                'created_at'           => $now,
                'updated_at'           => $now,
            ],

            [
                'account_id'           => 6,
                'contact_recording_id' => null,
                'for_repossession_id' => 2,
                'collection_id'        => null,
                'ar_number'            => 'AR-2026-0004',
                'ar_date'              => $now,
                'amount'               => 1200.75,
                'receipt_image_path'   => null, // Example of an entry without an uploaded photo yet
                'created_at'           => $now,
                'updated_at'           => $now,
            ]




        ];

        ReceiptEncoding::insert($records);
    }
}
