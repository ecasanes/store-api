<?php

use App\PaymentMode;
use Illuminate\Database\Seeder;

class PaymentModesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentMode::firstOrCreate([
            'id' => 1,
            'name' => 'Debit',
            'code' => 'debit'
        ]);

        PaymentMode::firstOrCreate([
            'id' => 2,
            'name' => 'Credit',
            'code' => 'credit'
        ]);

        PaymentMode::firstOrCreate([
            'id' => 3,
            'name' => 'Cash on Delivery',
            'code' => 'cod'
        ]);
    }
}
