<?php

namespace Database\Seeders;

use App\Models\RedemptionType;
use Illuminate\Database\Seeder;

class RedemptionTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RedemptionType::create(['name' => 'Cash']);
        RedemptionType::create(['name' => 'Bank transfer']);
        RedemptionType::create(['name' => 'Paypal']);
        RedemptionType::create(['name' => 'Mobile money']);
    }
}
