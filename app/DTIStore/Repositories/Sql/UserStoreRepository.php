<?php namespace App\DTIStore\Repositories;

use App\DTIStore\Helpers\StatusHelper;
use App\UserStore;

class UserStoreRepository implements UserStoreInterface {

    public function create(array $data)
    {
        // find user roles that are not yet deleted, if there is one, just retrieve
        $data['deleted_at'] = null;

        $userStore = UserStore::firstOrCreate($data);

        return $userStore;
    }

    public function find($id)
    {
        $userStore = UserStore::find($id);

        return $userStore;
    }

    public function getAll()
    {
        $userStores = UserStore::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $userStores;
    }

    public function filter(array $filter)
    {
        // TODO: filters
        $userStores = $this->getAll();

        return $userStores;
    }

    public function update($id, $data)
    {
        $userStore = $this->find($id);

        if(!$userStore){
            return false;
        }

        $updated = $userStore->update($data);

        return $updated;
    }

    public function updateByUserId($userId, $storeId)
    {
        $updated = UserStore::where('user_id',$userId)->update([
            'store_id' => $storeId
        ]);

        return $updated;
    }

    public function findStoreIdByUser($userId)
    {
        $userStore = UserStore::where('user_id', $userId)->first();

        if(!$userStore){
            return null;
        }

        return $userStore->store_id;
    }
}