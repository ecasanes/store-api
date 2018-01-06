<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\StatusHelper;
use App\Permission;
use App\UserPermission;

class UserPermissionRepository implements UserPermissionInterface {

    public function create(array $data)
    {
        // find user roles that are not yet deleted, if there is one, just retrieve
        $data['deleted_at'] = null;

        $userPermission = UserPermission::firstOrCreate($data);

        return $userPermission;
    }

    public function find($id)
    {
        $userPermission = UserPermission::find($id);

        return $userPermission;
    }

    public function findPermissionByCode($code)
    {
        $permission = Permission::where('code', $code)->first();

        return $permission;
    }

    public function findUserPermissionsByUserId($id)
    {
        $userPermissions = UserPermission::where('user_id', $id)->get()->toArray();

        return $userPermissions;
    }

    public function getAll()
    {
        $userPermissions = UserPermission::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $userPermissions;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $userPermissions = $this->getAll();

        return $userPermissions;
    }

    public function update($id, $data)
    {
        $userPermission = $this->find($id);

        if(!$userPermission){
            return false;
        }

        $updated = $userPermission->update($data);

        return $updated;
    }

    public function getPermissionsByUserId($userId)
    {
        $roles = UserPermission::where('user_id', $userId)
            ->join('permissions','permissions.id','=','user_permissions.permission_id')
            ->where('permissions.status',StatusHelper::ACTIVE)
            ->pluck('permissions.code')
            ->all();

        return array_unique($roles);
    }

    public function createUserPermissionsByCode($userId, $permissionCodes = [])
    {
        $userPermissions = [];

        if(empty($permissionCodes)){
            return $userPermissions;
        }

        foreach($permissionCodes as $permissionCode){

            $permission = $this->findPermissionByCode($permissionCode);

            if(!$permission){
                continue;
            }

            $permissionId = $permission->id;

            $userPermissions[] = UserPermission::firstOrCreate([
                'user_id' => $userId,
                'permission_id' => $permissionId
            ]);

        }

        return $userPermissions;
    }

    public function updateUserPermissionByCode($userId, $userPermissions)
    {
        $permissionsToUpdate = $this->buildPermissionsArray($userPermissions);
        $permissionsToUpdate = (array)$permissionsToUpdate;
        $existingUserPermissions = $this->findUserPermissionsByUserId($userId);

        foreach($permissionsToUpdate as $permissionKey => $permission) {
            foreach($existingUserPermissions as $userPermissionKey => $userPermission) {
                if($permission['id'] == $userPermission['permission_id']) {
                    unset($permissionsToUpdate[$permissionKey]);
                    unset($existingUserPermissions[$userPermissionKey]);
                }
            }
        }

        if(!empty($existingUserPermissions)) {
            foreach($existingUserPermissions as $userPermission) {
                $this->deleteUserPermissionByUserIdAndPermissionId($userId, $userPermission['permission_id']);
            }
        }

        if(!empty($permissionsToUpdate)) {
            foreach($permissionsToUpdate as $permissionToBeAdded) {
                $this->createUserPermission($userId, $permissionToBeAdded['id']);
            }
        }

        $userPermissions = $this->findUserPermissionsByUserId($userId);

        return $userPermissions;
    }

    private function deleteUserPermissionByUserIdAndPermissionId($userId, $permissionId)
    {
        $deleted = UserPermission::where('permission_id', $permissionId)->where('user_id', $userId)->delete();

        return $deleted;
    }

    private function createUserPermission($userId, $permissionId)
    {
        $userPermission = UserPermission::create([
            'user_id' => $userId,
            'permission_id' => $permissionId
        ]);

        return $userPermission;
    }

    private function buildPermissionsArray($userPermissions)
    {
        foreach($userPermissions as $key => $permission) {
            $permission = $this->findPermissionByCode($permission);
            $permissions[$key]['id'] = $permission->id;
            $permissions[$key]['code'] = $permission;
        }

        return $permissions;
    }
}