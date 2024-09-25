<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::create([
            'rate_per_km' => 10.0, 
            'commission' => 10.0, 
            'currency_id' => 97,
            'publish_trips_future_days' => 3,
            'max_distance_to_stop' => 10.0,
            'distance_to_stop_to_mark_arrived' => 100
        ]);
    }
}
