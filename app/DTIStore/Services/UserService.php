<?php namespace App\DTIStore\Services;

use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Repositories\StoreInterface;
use App\DTIStore\Repositories\RoleInterface;
use App\DTIStore\Repositories\UserInterface;
use App\DTIStore\Repositories\UserRoleInterface;
use App\DTIStore\Repositories\UserPermissionInterface;
use App\DTIStore\Repositories\UserStoreInterface;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $user;
    protected $role;
    protected $userRole;
    protected $userStore;
    protected $userPermission;
    protected $branch;

    public function __construct(
        UserInterface $user,
        RoleInterface $role,
        UserRoleInterface $userRole,
        UserStoreInterface $userStore,
        UserPermissionInterface $userPermission,
        StoreInterface $branch
    )
    {
        $this->user = $user;
        $this->role = $role;
        $this->userRole = $userRole;
        $this->userStore = $userStore;
        $this->userPermission = $userPermission;
        $this->branch = $branch;
    }

    public function create(array $data)
    {
        $role = null;
        $roleId = null;
        $userId = null;

        if (!isset($data['password'])) {
            $data['password'] = Hash::make(str_random());
        }

        $user = $this->user->create($data);

        if (!$user) {
            return false;
        }

        $userId = $user->id;

        if (isset($data['role'])) {
            $role = $this->role->findByCode($data['role']);
        }

        if ($role) {
            $roleId = $role->id;
            $this->createUserRole($userId, $roleId);
        }

        return $user;
    }

    public function createUserRole($userId, $roleId)
    {

        $userRole = $this->userRole->create([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);

        return $userRole;

    }

    public function createUserPermissionsByCode($userId, $permissionCodes = [])
    {
        if (empty($permissionCodes)) {
            return false;
        }

        $this->userPermission->createUserPermissionsByCode($userId, $permissionCodes);

    }

    public function createUserPermissionByCode($userId, $permissionCode)
    {
        return $this->createUserPermissionsByCode($userId, [$permissionCode]);

    }

    public function updateCustomerId($userId, $customerId)
    {

        $updated = $this->customerUser->updateCustomerId($userId, $customerId);

        return $updated;

    }

    public function find($id)
    {
        $userId = null;

        $user = $this->user->find($id);

        if (!$user) {
            return $user;
        }

        $userId = $user->id;

        /*$roles = $this->getRolesByUserId($userId);
        $user->roles = $roles;*/

        $role = $this->findRoleByUserId($userId);
        $user->role = $role;

        $permissions = $this->getPermissionsByUserId($userId);
        $user->permissions = $permissions;

        return $user;
    }

    public function findRoleByUserId($userId)
    {
        $role = $this->userRole->findRoleByUserId($userId);

        return $role;
    }

    public function getAll()
    {
        $users = $this->user->getAll();

        return $users;

    }

    public function getPermissionsByUserId($userId)
    {
        $hasPermissions = $this->hasPermissions($userId);

        if (!$hasPermissions) {
            return [];
        }

        $permissions = $this->userPermission->getPermissionsByUserId($userId);

        return $permissions;
    }

    public function getRolesByUserId($userId)
    {
        $roles = $this->userRole->getRolesByUserId($userId);

        return $roles;
    }

    public function isDeleted($id)
    {
        $isDeleted = $this->user->isDeleted($id);

        return $isDeleted;
    }

    private function hasPermissions($userId)
    {
        $hasPermission = $this->userRole->hasPermissions($userId);

        return $hasPermission;
    }

    public function update($id, $data)
    {
        $updated = $this->user->update($id, $data);

        return $updated;
    }

    public function updateUserPermissionsByCode($userId, array $permissions = [])
    {
        if (empty($permissions)) {
            return false;
        }

        return $this->userPermission->updateUserPermissionByCode($userId, $permissions);
    }

    public function updateUserPermissionByCode($userId, $permission)
    {
        return $this->updateUserPermissionsByCode($userId, [$permission]);
    }

    public function delete($id)
    {
        $deleted = $this->user->delete($id);

        return $deleted;
    }

    public function filter($data)
    {
        $users = $this->user->filter($data);

        return $users;
    }

    public function getFilterMeta($data)
    {
        $meta = $this->user->getFilterMeta($data);

        return $meta;
    }

    public function getCompanyManagerPermissionByUserId($id)
    {
        $data = $this->user->getCompanyManagerPermissionByUserId($id);

        return $data;
    }

    public function findByCustomerId($customerId)
    {
        $user = $this->user->findByCustomerId($customerId);

        return $user;
    }

    public function findByFirstnameAndLastname($firstname, $lastname)
    {
        $user = $this->user->findByFirstnameAndLastname($firstname, $lastname);

        return $user;
    }

    public function getCountByFilter($filter)
    {
        $total = $this->user->getCountByFilter($filter);

        return $total;
    }

    public function getFranchiseeByEmail($email)
    {
        $user = $this->user->getFranchiseeByEmail($email);

        return $user;

    }

    public function createMembers(array $members)
    {
        $newMembers = [];

        foreach ($members as $member) {

            $customerId = $member['customer_id'];
            $firstname = $member['firstname'];
            $lastname = $member['lastname'];

            $member = $this->createMember($customerId, $firstname, $lastname);
            $newMembers[] = $member;

        }

        return $newMembers;

    }

    private function createMember($customerId, $firstname, $lastname)
    {

        $user = $this->user->create([
            'firstname' => $firstname,
            'lastname' => $lastname
        ]);

        if (!$user) {
            return false;
        }

        $customerUser = $this->customerUser->findByCustomerId($customerId);

        if($customerUser){
            return false;
        }

        $customer = $this->customerUser->create([
            'customer_id' => $customerId,
            'user_id' => $user->id
        ]);

        return $customer;

    }

    public function updateUserRole($userId, $roleId)
    {
        $updated = $this->userRole->updateByUserId($userId, $roleId);

        return $updated;
    }

    public function createUserStore($userId, $storeId)
    {
        $userStore = $this->userStore->create([
            'user_id' => $userId,
            'store_id' => $storeId
        ]);

        return $userStore;
    }

    public function findStoreIdByUser($userId)
    {
        $storeId = $this->userStore->findStoreIdByUser($userId);

        return $storeId;
    }

}