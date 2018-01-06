<?php

use App\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::firstOrCreate([
            'id' => 1,
            'name' => 'Administrator',
            'rank' => 1,
            'code' => 'admin',
            'status' => 'active'
        ]);

        Role::firstOrCreate([
            'id' => 9,
            'name' => 'Seller',
            'rank' => 2,
            'code' => 'seller',
            'status' => 'active'
        ]);

        Role::firstOrCreate([
            'id' => 10,
            'name' => 'Buyer',
            'rank' => 3,
            'code' => 'buyer',
            'status' => 'active'
        ]);
    }
}
