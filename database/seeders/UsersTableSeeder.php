<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Traits\UserUtils;

class UsersTableSeeder extends Seeder
{
    use UserUtils;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create super admin account
        $adminUser = User::create([
            'name' => 'SuperAdmin',
            'email' => 'admin@tranzo.com',
            'password' => Hash::make('tranzo225#'),
            'role' => 0,
            'status_id' => 1,
            'uid' => "hLKE79LqQgSDQ62AxTb1JSg2neu1"
        ]);
        $this->storeAvatar($adminUser);
    }
}
