<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController as BaseController;

class RolesPermissionController extends BaseController
{
    /**
     * List roles and permissions
     *
     * @param  Request  $request
     */
    public function list(Request $request)
    {
        $roles = Role::all();
        $permissions = Permission::all();

        return $this->sendResponse([
            'roles' => $roles,
            'permissions' => $permissions,
        ], 'Roles and permissions retrieved successfully.');
    }

    /**
     * Create role and permission
     *
     * @param  Request  $request
     */
    public function create(Request $request)
    {
        $role = null;
        $permission = null;

        if ($request->role != null) {
            $validated = Validator::make($request->all(), [
                'role.name' => 'required|string',
                'role.guard_name' => 'required|string',
                'role.shipper_id' => 'required|integer',
            ]);

            if ($validated->fails()) {
                return $this->sendError('Validation failed.', $validated->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }

            $role = Role::make(['name'=> $request->role['name'], 'guard_name' => $request->role['guard_name'], 'shipper_id' => $request->role['shipper_id']]);

            if (!$role->saveOrFail()) {
                return $this->sendError('Error on role creation', $role->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }
        } else {
            $validated = Validator::make($request->all(), [
                'permission.name' => 'required|string',
                'permission.guard_name' => 'required|string',
                'permission.role.shipper_id' => 'required|integer',
            ]);

            if ($validated->fails()) {
                return $this->sendError('Validation failed.', $validated->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }

            $role = Role::findByName($request->permission['role']['name'], $request->permission['role']['guard_name']);
            
            $permission = Permission::make(['name' => $request->permission['name'], 'guard_name' => $request->permission['guard_name'], 'shipper_id' => $request->permission['role']['shipper_id']]);

            if ($permission->saveOrFail()) {
                return $this->sendError('Error on permission creation', $permission->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }

            $permission->assignRole($role);
        }

        return $this->sendResponse([
            'role' => $role,
            'permission' => $permission,
        ], 'Role and permission created successfully.');
    }

    /**
     * Update role and permission
     *
     * @param  Request  $request
     * @param  int  $id
     */
    public function destroy(Request $request, $id)
    {
        $role = null;
        $permission = null;

        if ($request->role != null) {
            $role = Role::findById($id);
            $role->delete();
        } else {
            $permission = Permission::findById($id);
            $permission->delete();
        }

        return $this->sendResponse([
            'role' => $role,
            'permission' => $permission,
        ], 'Role deleted successfully.');
    }

    public function update(Request $request, $id)
    {
        $role = null;
        $permission = null;

        if ($request->role != null) {
            $role = Role::findById($id);

            $validated = Validator::make($request->all(), [
                'role.name' => 'required|string',
                'role.guard_name' => 'required|string',
            ]);

            if ($validated->fails()) {
                return $this->sendError('Validation failed.', $validated->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }

            $role->update(['name' => $request->role['name'], 'guard_name' => $request->role['guard_name']]);
        } else {
            $permission = Permission::findById($id);
            $validated = Validator::make($request->all(), [
                'permission.name' => 'required|string',
                'permission.guard_name' => 'required|string',
            ]);

            if ($validated->fails()) {
                return $this->sendError('Validation failed.', $validated->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }

            $permission->update(['name' => $request->permission['name'], 'guard_name' => $request->permission['guard_name']]);
        }

        return $this->sendResponse([
            'role' => $role,
            'permission' => $permission,
        ], 'Role updated successfully.');
    }

    /**
     * Assign permission to role
     *
     * @param  Request  $request
     * @param  int  $id
     */
    public function assignRole(Request $request)
    {
        logger($request->all());
        $role = Role::findByName($request->assign['role']['name'], $request->assign['role']['guard_name']);
        $user = User::find($request->assign['user_id']);

        $user->assignRole($role);

        return $this->sendResponse([
            'role' => $role,
            'user' => $user,
        ], 'Role assigned user successfully.');
    }
}
