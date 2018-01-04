<?php namespace App\Mercury\Repositories;

use App\Mercury\Helpers\StatusHelper;
use App\Role;
use Carbon\Carbon;

class RoleRepository implements RoleInterface {

    public function create(array $data)
    {
        $role = Role::create($data);

        return $role;
    }

    public function find($id)
    {
        $role = Role::find($id);

        return $role;
    }

    public function findByCode($code)
    {
        $role = Role::where('code',$code)->first();

        return $role;
    }

    public function getAll()
    {
        $roles = Role::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $roles;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $roles = $this->getAll();

        return $roles;
    }

    public function update($id, $data)
    {
        $role = $this->find($id);

        if(!$role){
            return false;
        }

        $updated = $role->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $role = $this->find($id);

        if(!$role){
            return false;
        }

        if($role->status == StatusHelper::DELETED){
            return true;
        }

        $deleted = $role->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
            'name' => StatusHelper::flagDelete($role->name),
            'code' => StatusHelper::flagDelete($role->code)
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $role = $this->find($id);

        if(!$role){
            return false;
        }

        $destroyed = $role->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $role = $this->find($id);

        if(!$role){
            return true;
        }

        if($role->status != StatusHelper::DELETED){
            return false;
        }

        return true;
    }

    public function getHighRankingRoles($code, $include = true)
    {
        $role = $this->findByCode($code);

        if(!$role){
            return [];
        }


        $operator = "<=";

        if(!$include){
            $operator = "<";
        }

        $rank = $role->rank;

        $roles = Role::where('rank',$operator,$rank)->pluck('code')->all();

        return $roles;
    }
}