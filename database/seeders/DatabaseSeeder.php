<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CurrencySeeder::class);
        $this->call(StatusesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        //$this->call(BusesTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(RedemptionTypesTableSeeder::class);
    }
}
