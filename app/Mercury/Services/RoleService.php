<?php namespace App\Mercury\Services;

use App\Mercury\Repositories\RoleInterface;
use App\Mercury\Repositories\UserRoleInterface;

class RoleService
{
    protected $role;
    protected $userRole;

    public function __construct(RoleInterface $role, UserRoleInterface $userRole)
    {
        $this->role = $role;
        $this->userRole = $userRole;
    }

    public function create(array $data)
    {
        $role = $this->role->create($data);

        return $role;
    }

    public function find($id)
    {
        $role = $this->role->find($id);

        if(!$role){
            return $role;
        }

        return $role;
    }

    public function getAll()
    {
        $roles = $this->role->getAll();

        return $roles;

    }

    public function update($id, $data)
    {
        $updated = $this->role->update($id, $data);

        return $updated;
    }

    public function delete($id)
    {
        $deleted = $this->role->delete($id);

        return $deleted;
    }

    public function isDeleted($id)
    {
        $isDeleted = $this->role->isDeleted($id);

        return $isDeleted;
    }

    public function getHighRankingRoles($code, $include = true)
    {
        $roles = $this->role->getHighRankingRoles($code, $include);

        return $roles;
    }

    public function findByCode($code)
    {
        $role = $this->role->findByCode($code);

        return $role;
    }

    public function hasEnoughPermissionsByRole($baseRole, array $roleCodes)
    {

        foreach($roleCodes as $role){

            $acceptedRoles = $this->getHighRankingRoles($baseRole);

            if(in_array($role, $acceptedRoles)){
                return true;
            }

        }

        return false;
    }

}