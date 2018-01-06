<?php

namespace App\DTIStore\Tests;

use App\Store;
use App\BranchStaff;
use App\CustomerUser;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\CreatesApplication;

abstract class DTIStoreTestCase extends BaseTestCase
{
    use CreatesApplication;

    public function authorizeAdminGetToken()
    {
        return $this->authorizeGetToken('admin@email.com');
    }

    public function authorizeCompanyGetToken()
    {
        return $this->authorizeGetToken('company@email.com');
    }

    public function authorizeCompanyStaffInventoryGetToken()
    {
        return $this->authorizeGetToken('company.staff.inventory@email.com');
    }

    public function authorizeCompanyStaffSalesGetToken()
    {
        return $this->authorizeGetToken('company.staff.sales@email.com');
    }

    public function authorizeCompanyStaffGetToken()
    {
        return $this->authorizeGetToken('company.staff@email.com');
    }

    public function authorizeGetToken($email, $password = 'testing')
    {

        $response = $this->post('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        if (!$response) {
            return "";
        }

        $data = json_decode($response->getContent(), TRUE);

        return $data['token'];
    }

    public function getStandardStaffId()
    {

        $standardStaff = BranchStaff::where('user_id', 7)->where('status', 'active')->first();

        return $standardStaff->staff_id;

    }

    public function getCoordinatorStaffId()
    {

        $coordinatorStaff = BranchStaff::where('user_id', 6)->where('status', 'active')->first();

        $coordinatorStaff->update([
            'can_void' => 1
        ]);

        return $coordinatorStaff->staff_id;

    }

    public function getBranchKey()
    {
        // for testing use branch 1 always

        $branch = Store::find(1);

        return $branch->key;
    }

    public function getCustomerId()
    {
        $customers = CustomerUser::where('customer_id','!=',null)->get();

        foreach($customers as $customer){

            return $customer->customer_id;

        }

        return '';
    }

    private function postWithBranchCred($url, $body, $staffId, $branchKey)
    {

        $this->refreshApplication();

        $body = $body + [
                'staff_id' => $staffId,
                'key' => $branchKey
            ];

        return $this->post($url, $body);

    }

    private function getWithBranchCred($path, $staffId, $branchKey, $additionalRequests)
    {
        $this->refreshApplication();

        return $this->get($path . "?staff_id=" . $staffId . "&key=" . $branchKey . $additionalRequests);
    }

    private function postWithTokens($url, $body, $token)
    {
        $this->refreshApplication();

        return $this->post($url, $body, [
            'Authorization' => 'Bearer ' . $token
        ]);
    }

    private function deleteWithTokens($url, $body, $token)
    {
        $this->refreshApplication();

        return $this->delete($url, $body, [
            'Authorization' => 'Bearer ' . $token
        ]);
    }

    private function getWithTokens($url, $token)
    {
        $this->refreshApplication();

        return $this->get($url, [
            'Authorization' => 'Bearer ' . $token
        ]);
    }

    public function adminPost($url, $body = [])
    {
        $token = $this->authorizeAdminGetToken();

        return $this->postWithTokens($url, $body, $token);

    }

    public function standardStaffPost($url, $body = [])
    {
        $staffId = $this->getStandardStaffId();
        $branchKey = $this->getBranchKey();

        return $this->postWithBranchCred($url, $body, $staffId, $branchKey);

    }

    public function coordinatorStaffPost($url, $body = [])
    {
        $staffId = $this->getCoordinatorStaffId();
        $branchKey = $this->getBranchKey();

        return $this->postWithBranchCred($url, $body, $staffId, $branchKey);

    }

    public function staffGet($path, $additionalRequests)
    {
        $staffId = $this->getStandardStaffId();
        $branchKey = $this->getBranchKey();

        return $this->getWithBranchCred($path, $staffId, $branchKey, $additionalRequests);

    }

    public function adminDelete($url, $body = [])
    {
        $token = $this->authorizeAdminGetToken();

        return $this->deleteWithTokens($url, $body, $token);

    }

    public function adminGet($url)
    {
        $token = $this->authorizeAdminGetToken();

        return $this->getWithTokens($url, $token);

    }

    public function companyPost($url, $body)
    {
        $token = $this->authorizeCompanyGetToken();

        return $this->postWithTokens($url, $body, $token);

    }

    public function companyGet($url)
    {
        $token = $this->authorizeCompanyGetToken();

        return $this->getWithTokens($url, $token);

    }

    public function companyStaffPost($url, $body)
    {
        $token = $this->authorizeCompanyStaffGetToken();

        return $this->postWithTokens($url, $body, $token);

    }

    public function companyStaffGet($url)
    {
        $token = $this->authorizeCompanyStaffGetToken();

        return $this->getWithTokens($url, $token);

    }

    public function companyStaffInventoryPost($url, $body)
    {
        $token = $this->authorizeCompanyStaffInventoryGetToken();

        return $this->postWithTokens($url, $body, $token);

    }

    public function companyStaffInventoryGet($url)
    {
        $token = $this->authorizeCompanyStaffInventoryGetToken();

        return $this->getWithTokens($url, $token);

    }

    public function companyStaffSalesPost($url, $body)
    {
        $token = $this->authorizeCompanyStaffSalesGetToken();

        return $this->postWithTokens($url, $body, $token);

    }

    public function companyStaffSalesGet($url)
    {
        $token = $this->authorizeCompanyStaffSalesGetToken();

        return $this->getWithTokens($url, $token);

    }
}
