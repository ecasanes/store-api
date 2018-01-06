<?php

use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use App\Store;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    protected $userService;
    protected $roleService;
    protected $password;

    public function __construct(
        UserService $userService,
        RoleService $roleService
    )
    {
        $this->userService = $userService;
        $this->roleService = $roleService;

        $this->password = Hash::make('123');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createAdmin();
        $this->createSeller();
        $this->createBuyer();

    }

    private function createAdmin()
    {
        $admin = User::firstOrCreate([
            'email' => 'admin@email.com',
            'firstname' => 'Admin',
            'lastname' => '',
            'password' => $this->password
        ]);

        $adminRole = $this->roleService->findByCode('admin');

        if ($adminRole) {
            $this->userService->createUserRole($admin->id, $adminRole->id);
        }
    }

    private function createSeller()
    {
        $seller = User::firstOrCreate([
            'email' => 'seller@email.com',
            'firstname' => 'Seller',
            'lastname' => '',
            'password' => $this->password
        ]);

        $sellerRole = $this->roleService->findByCode('seller');

        if ($sellerRole) {
            $this->userService->createUserRole($seller->id, $sellerRole->id);
        }

        $store = Store::firstOrCreate([
            'name' => 'Online Store 1',
            'key' => 11111111
        ]);

        if($store){
            $this->userService->createUserStore($seller->id, $store->id);
        }
    }

    private function createBuyer()
    {
        $buyer = User::firstOrCreate([
            'email' => 'buyer@email.com',
            'firstname' => 'Buyer',
            'lastname' => '',
            'password' => $this->password
        ]);

        $buyerRole = $this->roleService->findByCode('buyer');

        if ($buyerRole) {
            $this->userService->createUserRole($buyer->id, $buyerRole->id);
        }
    }
}
