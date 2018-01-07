<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class);

        $this->call(UsersTableSeeder::class);

        // new
        $this->call(PaymentModesSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(ProductConditionsTableSeeder::class);
    }
}
