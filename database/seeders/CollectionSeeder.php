<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Collection;
use Carbon\Carbon;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        Collection::create([
            'total_amount' => 9750.5,
            'total_account' => 3,
            'created_at' => $now->copy()->subDays()
        ]);

        Collection::create([
            'total_amount' => 3602.25,
            'total_account' => 3,
            'created_at' => $now
        ]);
    }
}
