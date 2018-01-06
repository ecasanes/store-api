<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use App\DTIStore\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    protected $userService;
    protected $roleService;
    protected $companyService;
    protected $exportService;

    public function __construct(
        Request $request,
        UserService $userService,
        RoleService $roleService,
        StoreService $companyService,
        ExportService $exportService
    )
    {
        parent::__construct($request);

        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->companyService = $companyService;
        $this->exportService = $exportService;
    }

    public function getAll()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $usersMeta = $this->userService->getFilterMeta($data);
        $users = $this->userService->filter($data);

        return Rest::success($users, $usersMeta);
    }

    public function get($id)
    {
        $user = $this->userService->find($id);

        if (!$user) {
            return Rest::notFound("User not found");
        }

        return Rest::success($user);
    }

    public function getCompanyPermissions($id)
    {
        $permissions = $this->userService->getCompanyManagerPermissionByUserId($id);

        return Rest::success($permissions);
    }

    public function getByCustomerId($customerId)
    {
        $user = $this->userService->findByCustomerId($customerId);

        if (!$user) {
            return Rest::notFound("Customer not found");
        }

        return Rest::success($user);
    }

    public function getCustomers()
    {
        $payload = $this->payload;
        $data = $payload->all();

        /* $key = $payload->key;

         $branch = $this->companyService->findBranchByKey($key);

         if(!$branch){
             return Rest::failed("Branch not found");
         }

         $branchId = $branch->id;
         $data['branch_id'] = $branchId;*/

        $data['roles'] = "member,guest";

        $usersMeta = $this->userService->getFilterMeta($data);
        $users = $this->userService->filter($data);

        return Rest::success($users, $usersMeta);
    }

    public function getCountByFilter()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $total = $this->userService->getCountByFilter($data);

        return Rest::success($total);

    }

    public function createCustomerFromBranchStaff()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $branchKey = $payload->key;
        $staffId = $payload->staff_id;

        $branchId = null;
        $roleCode = null;
        $additionalValidations = [];
        $email = null;

        $branch = $this->companyService->findBranchByKey($branchKey);

        if (!$branch) {
            return Rest::notFound("Cannot add member. Please double check if you've entered Branch Key in the settings page.");
        }

        $branchId = $branch->id;

        if (isset($data['email'])) {
            $additionalValidations = [
                'email' => 'email|unique:users'
            ];
        }

        if (isset($data['customer_id'])) {
            $additionalValidations['customer_id'] = 'unique:customer_users|digits_between:1,9';
        }

        $validator = $this->validator($data, [
                'firstname' => 'required',
                'lastname' => 'required',
                'role' => 'required',
            ] + $additionalValidations, [
            'customer_id.unique' => "Account number is already registered.",
            'customer_id.digits_between' => "Account number must not exceed to 9 digits."
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $roleCode = $data['role'];
        $role = $this->roleService->findByCode($roleCode);

        if ($roleCode != StatusHelper::MEMBER && $roleCode != StatusHelper::GUEST) {
            return Rest::failed("You can only add Customer and Guest");
        }

        if (!$role) {
            return Rest::failed("Invalid role!");
        }

        /*if(isset($data['email'])){
            $email = $data['email'];
        }*/

        $firstname = $data['firstname'];
        $lastname = $data['lastname'];

        $existingCustomer = $this->userService->findByFirstnameAndLastname($firstname, $lastname);

        if (!$email && $existingCustomer) {
            return Rest::failed("There's already been a customer registered with same full name.");
        }

        $password = Hash::make($payload->password);
        $data['password'] = $password;
        $data['branch_id_registered'] = $branchId;

        $user = $this->userService->create($data);

        if (!$user) {
            return Rest::failed("Something went wrong while creating new user. Please contact customer support.");
        }

        $userId = $user->id;

        if (isset($data['customer_id'])) {
            $customerId = $data['customer_id'];
            $this->userService->updateCustomerId($userId, $customerId);
        }

        if (!isset($data['customer_id'])) {
            $this->userService->generateCustomerId($userId);
        }


        $user = $this->userService->find($userId);

        return Rest::success($user);

    }

    public function createGuestFromBranch()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $branchKey = $payload->key;

        $branchId = null;
        $roleCode = null;
        $additionalValidations = [];
        $email = null;

        $branch = $this->companyService->findBranchByKey($branchKey);

        if (!$branch) {
            return Rest::notFound("Cannot add member. Please double check if you've entered Branch Key in the settings page.");
        }

        $branchId = $branch->id;

        if (isset($data['email'])) {
            $additionalValidations = [
                'email' => 'email|unique:users'
            ];
        }

        $validator = $this->validator($data, [

                'firstname' => 'required',
                'lastname' => 'required',
            ] + $additionalValidations);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $roleCode = StatusHelper::GUEST;
        $role = $this->roleService->findByCode($roleCode);

        if ($roleCode != StatusHelper::GUEST) {
            return Rest::failed("You can only add Guest");
        }

        if (!$role) {
            return Rest::failed("Invalid role!");
        }

        $password = Hash::make(str_random(8));
        $data['password'] = $password;
        $data['branch_id_registered'] = $branchId;

        $user = $this->userService->create($data);

        if (!$user) {
            return Rest::failed("Something went wrong while creating new user");
        }

        $userId = $user->id;
        $this->userService->generateCustomerId($userId);

        $user = $this->userService->find($userId);

        return Rest::success($user);

    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();
        $branchId = null;
        $roleCode = null;
        $canVoid = 0;

        $permissions = [];
        $additionalValidations = [];

        if (isset($data['role'])) {
            $roleCode = $data['role'];
        }

        if (isset($data['can_void'])) {
            $canVoid = $data['can_void'];
        }

        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
        }

        if (isset($data['branch_id'])) {
            $branchId = $data['branch_id'];
        }

        # default password
        $password = "testing";

        if ($roleCode != StatusHelper::STAFF && $roleCode != StatusHelper::MEMBER || $canVoid == 1) {

            $password = $payload->password;

            $additionalValidations = [
                'password' => 'required'
            ];
        }

        $validator = $this->validator($data, [
                'email' => 'required|email|unique:users',
                'firstname' => 'required',
                'lastname' => 'required',
                'role' => 'required',
                'branch_id' => 'required'
            ] + $additionalValidations, ['branch_id.required' => 'Please select the store you are registering from']);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $roleCode = $data['role'];
        $role = $this->roleService->findByCode($roleCode);

        if (!$role) {
            return Rest::failed("Invalid role!");
        }

        if (!is_array($permissions)) {
            return Rest::failed("Please input correct permission field format");
        }

        if ($permissions == "") {
            return Rest::failed("Please input correct permission field format");
        }

        if ($roleCode == StatusHelper::COMPANY_STAFF && empty($permissions)) {
            return Rest::failed("Please select at least one permission for this user type");
        }

        $password = Hash::make($password);
        $data['password'] = $password;

        $user = $this->userService->create($data);

        if (!$user) {
            return Rest::failed("Something went wrong while creating new user");
        }

        $userId = $user->id;
        $this->userService->createUserPermissionsByCode($userId, $permissions);

        if ($branchId && ($roleCode == StatusHelper::STAFF || $roleCode == StatusHelper::BRANCH)) {

            $staffId = $this->userService->generateStaffId($userId, $branchId);

            $this->companyService->updateStaff($staffId, [
                'can_void' => $canVoid
            ]);

            $this->userService->update($userId, [
                'branch_id_registered' => $branchId
            ]);
        }

        if ($roleCode == StatusHelper::MEMBER || $roleCode == StatusHelper::GUEST) {
            $this->userService->generateCustomerId($userId);
        }

        $user = $this->userService->find($userId);

        return Rest::success($user);

        // NOTE: in order for normal validations to work in API use
        // [{"key":"X-Requested-With","value":"XMLHttpRequest"}]

    }

    public function update($id)
    {
        $payload = $this->payload;
        $data = $payload->all();
        $permissions = [];
        $additionalValidations = [];
        $roleCode = null;

        $user = $this->userService->find($id);

        // if the user account is not found
        if (!$user) {
            return Rest::notFound("User not found.");
        }

        if (isset($data['role'])) {
            $roleCode = $data['role'];
        }

        /*if($roleCode != StatusHelper::STAFF){
            $additionalValidations = [
                'password' => 'required',
            ];
        }*/

        $validator = $this->validator($data, [
                'email' => 'email|unique:users,email,' . $id
            ] + $additionalValidations);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $isDeleted = $this->userService->isDeleted($id);

        if ($isDeleted) {
            return Rest::notFound("User is not active. Please contact the administrator.");
        }

        if (isset($data['role'])) {
            $roleCode = $data['role'];
            $role = $this->roleService->findByCode($roleCode);

            if (!$role) {
                return Rest::failed("Invalid role!");
            }
        }

        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
        }

        if (!is_array($permissions)) {
            return Rest::failed("Please input correct permission field format");
        }

        if ($permissions == "") {
            return Rest::failed("Please input correct permission field format");
        }

        $updated = $this->userService->update($id, $data);

        $this->userService->updateUserPermissionsByCode($id, $permissions);


        $user = $this->userService->find($id);

        return Rest::updateSuccess($updated, $user);
    }

    public function delete($id)
    {
        $deleted = $this->userService->delete($id);

        return Rest::deleteSuccess($deleted);
    }

    public function login()
    {
        $payload = $this->payload;

        $token = $payload->attributes->get('token');

        return Rest::successToken($token);
    }

    public function forgotPassword($id)
    {
        // TODO: forgotPassword - send an email containing reset link
    }

    public function resetPassword($id)
    {
        // TODO: resetPassword - update user's password using the link provided and then delete the reset code
    }

    public function export()
    {
        $data = $this->payload->all();

        $export = $this->exportService->export($data);

        if (!$export) {
            return Rest::failed("Data might not exist on the database. Please try again");
        }

        $path = url('uploads/exports/' . $export);

        return Rest::success($path);
    }

    public function refreshToken()
    {
        $payload = $this->payload;

        $token = $payload->attributes->get('token');

        return Rest::successToken($token);
    }

    public function getFranchiseeByEmail()
    {
        $data = $this->payload->all();

        $email = $data['email'];

        if (!$email) {
            return Rest::failed('Invalid request');
        }

        $user = $this->userService->getFranchiseeByEmail($email);

        return Rest::success($user);
    }

    public function getByStaffId($staffId)
    {
        $user = $this->companyService->findUserByStaffId($staffId);

        if (!$user) {
            return Rest::notFound("Staff #" . $staffId . " was not found.");
        }

        return Rest::success($user);

    }

    public function createNewCustomers()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $validator = $this->validator($data, [
            'customers' => 'required'
        ]);

        if ($validator->fails()) {
            return Rest::validationFailed($validator);
        }

        $newCustomers = $data['customers'];

        if (empty($data['customers'])) {
            return Rest::failed("No new customers to be inserted");
        }

        $membersCreated = $this->userService->createMembers($newCustomers);

        if (!$membersCreated) {
            return Rest::failed("Something went wrong while syncing new customers");
        }

        return Rest::success($membersCreated);

    }

}
