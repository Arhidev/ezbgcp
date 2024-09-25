<?php

namespace Database\Seeders;

use App\Models\Bus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $bus = Bus::create([
            'license' => 'B 1234',
            'capacity' => 10,
            'driver_id' => 3,
        ]);
    }
}
