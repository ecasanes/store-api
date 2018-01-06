<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\StatusHelper;
use App\UserRole;

class UserRoleRepository implements UserRoleInterface {

    public function create(array $data)
    {
        // find user roles that are not yet deleted, if there is one, just retrieve
        $data['deleted_at'] = null;

        $userRole = UserRole::firstOrCreate($data);

        return $userRole;
    }

    public function find($id)
    {
        $userRole = UserRole::find($id);

        return $userRole;
    }

    public function getAll()
    {
        $userRoles = UserRole::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $userRoles;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $userRoles = $this->getAll();

        return $userRoles;
    }

    public function update($id, $data)
    {
        $userRole = $this->find($id);

        if(!$userRole){
            return false;
        }

        $updated = $userRole->update($data);

        return $updated;
    }

    public function getRoleByUserId($id)
    {
        $userRole = UserRole::where('user_id', $id)->first();

        if(!$userRole) {
            return false;
        }

        return $userRole;

    }

    public function getRolesByUserId($userId)
    {
        $roles = UserRole::where('user_id', $userId)
            ->join('roles','roles.id','=','user_roles.role_id')
            ->where('roles.status',StatusHelper::ACTIVE)
            ->pluck('roles.code')
            ->all();

        return array_unique($roles);
    }

    public function findRoleByUserId($userId)
    {
        $role = UserRole::where('user_id', $userId)
            ->join('roles','roles.id','=','user_roles.role_id')
            ->where('roles.status',StatusHelper::ACTIVE)
            ->select('roles.*')
            ->orderBy('roles.rank')
            ->first();

        if(!$role){
            return null;
        }

        $roleCode = $role->code;

        return $roleCode;
    }

    public function hasPermissions($userId)
    {
        $role = UserRole::where('user_id', $userId)
            ->join('roles','roles.id','=','user_roles.role_id')
            ->where('roles.status',StatusHelper::ACTIVE)
            ->select('roles.*')
            ->orderBy('roles.rank')
            ->first();

        if(!$role){
            return false;
        }

        $hasPermissions = $role->has_permissions;

        return $hasPermissions;
    }

    public function updateByUserId($userId, $storeId)
    {
        $updated = UserRole::where('user_id',$userId)->update([
            'role_id' => $storeId
        ]);

        return $updated;
    }
}