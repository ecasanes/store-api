<?php

use App\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TransactionType::firstOrCreate([
            'name' => 'Add Stocks',
            'code' => 'add_stock',
            'group' => 'inventory'
        ]);

        TransactionType::firstOrCreate([
            'name' => 'Subtract Stocks',
            'code' => 'sub_stock',
            'group' => 'inventory'
        ]);

        TransactionType::firstOrCreate([
            'name' => 'Sale',
            'code' => 'sale',
            'group' => 'sale'
        ]);

        TransactionType::firstOrCreate([
            'name' => 'Return Sale',
            'code' => 'return_sale',
            'group' => 'sale'
        ]);

        TransactionType::firstOrCreate([
            'name' => 'Void Sale',
            'code' => 'void_sale',
            'group' => 'sale'
        ]);

    }
}
