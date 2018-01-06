<?php

use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use App\ProductCategory;
use App\Store;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CategoriesTableSeeder extends Seeder
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
        $categories = [
            'Bags',
            'Food',
            "Men's Apparel",
            "Women's Apparel",
            "Mobiles & gadgets",
            "Health & Beauty",
            "Consumer Electronics",
            "Toys, Kids & Babies",
            "Home & Living",
            "Men's Accessories",
            "Women's Accessories",
            "Men's Shoes",
            "Women's Shoes",
            "Sports & Outdoor",
            "Hobbies & Stationery",
            "Miscellaneous"
        ];

        foreach($categories as $category){
            ProductCategory::firstOrCreate([
                'name' => $category,
                'code' => str_slug($category)
            ]);
        }

    }
}
