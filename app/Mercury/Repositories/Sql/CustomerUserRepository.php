<?php namespace App\Mercury\Repositories;

use App\CustomerUser as Customer;
use App\Mercury\Helpers\StatusHelper;
use Carbon\Carbon;

class CustomerUserRepository implements CustomerUserInterface
{
    public function create(array $data)
    {
        $customer = Customer::create($data);

        return $customer;
    }

    public function find($id)
    {
        $customer = Customer::find($id);

        return $customer;
    }

    public function findByCustomerId($customerId)
    {
        $customer = Customer::where('customer_id', $customerId)->first();

        return $customer;
    }

    public function getAll()
    {
        $customers = Customer::whereIn('status', [StatusHelper::ACTIVE])->get();

        return $customers;
    }

    public function filter(array $filter)
    {
        // TODO: Implement filter() method.
    }

    public function update($id, $data)
    {
        $customer = $this->find($id);

        if (!$customer) {
            return false;
        }

        $updated = $customer->update($data);

        return $updated;
    }

    public function delete($id)
    {
        $customer = $this->find($id);

        if (!$customer) {
            return false;
        }

        if ($customer->status == StatusHelper::DELETED) {
            return true;
        }

        $deleted = $customer->update([
            'deleted_at' => Carbon::now()->toDateTimeString(),
            'status' => StatusHelper::DELETED,
        ]);

        return $deleted;
    }

    public function destroy($id)
    {
        $customer = $this->find($id);

        if (!$customer) {
            return false;
        }

        $destroyed = $customer->delete();

        return $destroyed;
    }

    public function isDeleted($id)
    {
        $customer = $this->find($id);

        if (!$customer) {
            return true;
        }

        if ($customer->status != StatusHelper::DELETED) {
            return false;
        }

        return true;
    }

    public function generateCustomerId($userId)
    {
        $customer = $this->findByUserId($userId);

        if ($customer) {
            return $customer->customer_id;
        }

        $customerIdNotUnique = true;

        $customerId = 0;

        while ($customerIdNotUnique) {

            $customerId = rand(10000000, 99999999); // TODO: need helpers

            $customerFromId = $this->findByCustomerId($customerId);

            if ($customerFromId) {
                continue;
            }

            $customer = $this->create([
                'customer_id' => $customerId,
                'user_id' => $userId
            ]);
            $customerIdNotUnique = false;
        }

        return $customerId;

    }

    public function updateCustomerId($userId, $customerId)
    {

        $customerFromId = $this->findByCustomerId($customerId);

        if ($customerFromId) {
            return false;
        }

        $this->create([
            'customer_id' => $customerId,
            'user_id' => $userId
        ]);


        return true;
    }

    private function findByUserId($userId)
    {
        $customer = Customer::where('user_id', $userId)
            ->first();

        return $customer;
    }

}