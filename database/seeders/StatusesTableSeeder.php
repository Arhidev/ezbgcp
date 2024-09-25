<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::create(['name' => 'active']);
        Status::create(['name' => 'pending']);
        Status::create(['name' => 'suspended']);
        Status::create(['name' => 'under_review']);
    }
}
