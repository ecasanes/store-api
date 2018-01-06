<?php namespace App\DTIStore\Services;

use App\DTIStore\Repositories\StoreInterface;
use App\DTIStore\Repositories\UserRoleInterface;

class StoreService
{
    protected $store;
    protected $userRole;

    public function __construct(
        StoreInterface $store,
        UserRoleInterface $userRole
    )
    {
        $this->store = $store;
        $this->userRole = $userRole;
    }

    public function generateStoreKey()
    {
        $key = null;
        $notUnique = true;

        while($notUnique){

            $key = rand(10000000, 99999999);

            $branch = $this->store->findByKey($key);

            if($branch){
                continue;
            }

            $notUnique = false;

        }

        return $key;
    }

    public function createStore(array $data)
    {
        $data['key'] = $this->generateStoreKey();
        $branch = $this->store->create($data);

        return $branch;
    }

    public function findStore($id)
    {
        $branch = $this->store->find($id);

        return $branch;
    }

    public function getAllStores($filter)
    {

        $branches = $this->store->filter($filter);

        return $branches;

    }

    public function updateStore($id, $data)
    {
        $updated = $this->store->update($id, $data);

        return $updated;
    }

    public function deleteStore($id)
    {
        $deleted = $this->store->delete($id);

        return $deleted;
    }

    public function isStoreDeleted($id)
    {
        $isDeleted = $this->store->isDeleted($id);

        return $isDeleted;
    }

    public function findStoreByKey($key)
    {
        $branch = $this->store->findByKey($key);

        return $branch;
    }

}