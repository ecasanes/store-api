<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\StatusHelper;
use App\Permission;

class PermissionRepository implements PermissionInterface {

    public function create(array $data)
    {
        $permission = Permission::create($data);

        return $permission;
    }

    public function find($id)
    {
        $permission = Permission::find($id);

        return $permission;
    }

    public function getAll()
    {
        $permissions = Permission::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $permissions;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $permissions = $this->getAll();

        return $permissions;
    }

    public function update($id, $data)
    {
        $permission = $this->find($id);

        if(!$permission){
            return false;
        }

        $updated = $permission->update($data);

        return $updated;
    }
}