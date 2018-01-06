<?php

use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use App\Role;
use App\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    protected $userService;
    protected $companyService;
    protected $roleService;
    protected $password;

    public function __construct(UserService $userService, StoreService $companyService, RoleService $roleService)
    {
        $this->userService = $userService;
        $this->companyService = $companyService;
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
}
