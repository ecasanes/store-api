<?php

use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use App\ProductCategory;
use App\ProductCondition;
use App\Store;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductConditionsTableSeeder extends Seeder
{

    public function __construct()
    {

    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $productConditions = [
            'New',
            'Used',
            'Used but good as new',
            'Used but good as new with flaws'
        ];

        foreach($productConditions as $condition){
            ProductCondition::firstOrCreate([
                'name' => $condition,
                'code' => str_slug($condition)
            ]);
        }

    }
}
