<?php

use App\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            [
                'name' => 'User Management',
                'code' => 'user',
            ],
            [
                'name' => 'Buyer Management',
                'code' => 'buyer'
            ],
            [
                'name' => 'Seller Management',
                'code' => 'seller'
            ],
            [
                'name' => 'Inventory Management',
                'code' => 'inventory'
            ],
            [
                'name' => 'Order Management',
                'code' => 'order'
            ]
        ];

        $counter = 1;
        foreach($permissions as $permission){

            Permission::firstOrCreate([
                'id' => $counter,
                'name' => $permission['name'],
                'code' => $permission['code']
            ]);

            $counter++;

        }
    }
}
