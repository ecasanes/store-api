<?php

namespace App\DTIStore\Api\Controllers;

use App\DTIStore\Helpers\Rest;
use App\DTIStore\Helpers\StatusHelper;
use App\DTIStore\Services\StoreService;
use App\DTIStore\Services\RoleService;
use App\DTIStore\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $userService;
    protected $roleService;
    protected $storeService;

    public function __construct(
        Request $request,
        UserService $userService,
        RoleService $roleService,
        StoreService $storeService
    )
    {
        parent::__construct($request);

        $this->userService = $userService;
        $this->roleService = $roleService;
        $this->storeService = $storeService;
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

    public function getCurrent()
    {
        return Rest::success($this->user);
    }

    public function getCountByFilter()
    {
        $payload = $this->payload;
        $data = $payload->all();

        $total = $this->userService->getCountByFilter($data);

        return Rest::success($total);

    }

    public function create()
    {
        $payload = $this->payload;
        $data = $payload->all();
        $branchId = null;
        $roleCode = null;
        $canVoid = 0;

        $permissions = [];

        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
        }

        $validator = $this->validator($data, [
                'email' => 'required|email|unique:users',
                'firstname' => 'required',
                'lastname' => 'required',
                'password' => 'required',
                'role' => 'required',
            ]);

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

        $password = $payload->password;
        $password = Hash::make($password);
        $data['password'] = $password;

        $user = $this->userService->create($data);

        if (!$user) {
            return Rest::failed("Something went wrong while creating new user");
        }

        $userId = $user->id;
        $this->userService->createUserPermissionsByCode($userId, $permissions);

        $user = $this->userService->find($userId);

        $store = $this->storeService->createStore([
            'name' => $user->email,
            'key' => $this->storeService->generateStoreKey()
        ]);

        $storeId = $store->id;

        $this->userService->createUserStore($userId, $storeId);

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

            $roleId = $role->id;

            $this->userService->updateUserRole($id, $roleId);
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

    public function refreshToken()
    {
        $payload = $this->payload;

        $token = $payload->attributes->get('token');

        return Rest::successToken($token);
    }

}
