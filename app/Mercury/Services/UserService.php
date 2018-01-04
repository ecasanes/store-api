<?php namespace App\Mercury\Services;

use App\Mercury\Helpers\StatusHelper;
use App\Mercury\Repositories\BranchInterface;
use App\Mercury\Repositories\BranchStaffInterface;
use App\Mercury\Repositories\CustomerUserInterface;
use App\Mercury\Repositories\RoleInterface;
use App\Mercury\Repositories\UserInterface;
use App\Mercury\Repositories\UserRoleInterface;
use App\Mercury\Repositories\UserPermissionInterface;
use Illuminate\Support\Facades\Hash;

class UserService
{
    protected $user;
    protected $role;
    protected $userRole;
    protected $userPermission;
    protected $branchStaff;
    protected $customerUser;
    protected $branch;

    public function __construct(
        UserInterface $user,
        RoleInterface $role,
        UserRoleInterface $userRole,
        UserPermissionInterface $userPermission,
        BranchStaffInterface $branchStaff,
        CustomerUserInterface $customerUser,
        BranchInterface $branch
    )
    {
        $this->user = $user;
        $this->role = $role;
        $this->userRole = $userRole;
        $this->userPermission = $userPermission;
        $this->branchStaff = $branchStaff;
        $this->customerUser = $customerUser;
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

    public function generateStaffId($userId, $branchId)
    {
        $staffId = $this->branchStaff->generateStaffId($userId, $branchId);

        return $staffId;
    }

    public function generateCustomerId($userId)
    {
        $customerId = $this->customerUser->generateCustomerId($userId);

        return $customerId;
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

    public function getStaffPrivilegesByUserId($userId)
    {
        $staffPrivileges = [];

        $staff = $this->branchStaff->findByUserId($userId);

        if (!$staff) {
            return $staffPrivileges;
        }

        $canVoid = $staff->can_void;
        $branchId = $staff->branch_id;

        if ($canVoid) {
            $staffPrivileges[] = StatusHelper::COORDINATOR;
        }

        $branch = $this->branch->find($branchId);

        if (!$branch) {
            return $staffPrivileges;
        }

        $branchType = $branch->type;

        if ($branchType) {
            $staffPrivileges[] = $branchType;
        }

        return $staffPrivileges;
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

        $this->branchStaff->updateByUserId($id, $data);

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

}