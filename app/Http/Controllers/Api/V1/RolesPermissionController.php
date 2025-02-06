<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\ShipperRole;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
        $user = auth()->user();

        // Get the module slugs (not the whole module objects) the user has access to
        $userPermissionSlugs = $user->modules->pluck('slug')->toArray();

        $roles = Role::where('guard_name', 'api')->get();
        $permissions = Permission::where('guard_name', 'api')->get();

        $shipperRoles = ShipperRole::where('shipper_id', $user->shipper_id)->get();

        // Group permissions first
        $groupedPermissions = $this->groupPermissions($permissions);

        // Filter groupedPermissions based on userPermissionSlugs
        $filteredGroupedPermissions = array_filter($groupedPermissions, function ($group) use ($userPermissionSlugs) {
            return in_array($group['title'], $userPermissionSlugs);
        });

        // Filter permissions within each group based on userPermissionSlugs
        $filteredGroupedPermissions = array_map(function ($group) use ($userPermissionSlugs) {
            $group['permissions'] = array_filter($group['permissions'], function ($permission) use ($userPermissionSlugs) {
                // Check if the permission name (e.g., "auth.create") starts with any of the allowed module slugs
                foreach ($userPermissionSlugs as $slug) {
                    if (str_starts_with($permission['name'], $slug . '.')) {
                        return true;
                    }
                }
                return false;
            });
            return $group;
        }, $filteredGroupedPermissions);

        // Remove groups that have no permissions after filtering
        $filteredGroupedPermissions = array_filter($filteredGroupedPermissions, function ($group) {
            return !empty($group['permissions']);
        });

        $roles = array_merge($roles->toArray(), $shipperRoles->toArray());

        return $this->sendResponse([
            'roles' => $roles,
            'modules' => array_values($filteredGroupedPermissions), // Reset array keys after filtering
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
                'role.guard_name' => 'required|string'
            ]);

            if ($validated->fails()) {
                return $this->sendError('Validation failed.', $validated->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }

            $role = Role::make(['name'=> $request->role['name'], 'guard_name' => $request->role['guard_name']]);

            if (!$role->saveOrFail()) {
                return $this->sendError('Error on role creation', $role->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }
        } else {
            $validated = Validator::make($request->all(), [
                'permission.name' => 'required|string',
                'permission.guard_name' => 'required|string'
            ]);

            if ($validated->fails()) {
                return $this->sendError('Validation failed.', $validated->errors(), JsonResponse::HTTP_BAD_REQUEST);
            }
            
            $permission = Permission::make(['name' => $request->permission['name'], 'guard_name' => $request->permission['guard_name']]);

            try {
                $permission->saveOrFail();
            } catch (\Exception $e) {
                return $this->sendError('Error on permission creation', $e->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
            }

            if (isset($request->permission['role'])) {
                $role = Role::findByName($request->permission['role']['name'], $request->permission['role']['guard_name']);
                
                $permission->assignRole($role);
            }
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
        $role = Role::findByName($request->assign['role']['name'], $request->assign['role']['guard_name']);
        $user = User::find($request->assign['user_id']);

        $defaultRole = Role::findByName('user', 'api');

        $user->removeRole($defaultRole);

        $user->assignRole($role);

        return $this->sendResponse([
            'role' => $role,
            'user' => $user,
        ], 'Role assigned user successfully.');
    }

    /**
     * Remove role from user
     *
     * @param  Request  $request
     */
    public function revokeRole(Request $request)
    {
        $role = Role::findByName($request->assign['role']['name'], $request->assign['role']['guard_name']);
        $user = User::find($request->assign['user_id']);

        $user->removeRole($role);

        $defaultRole = Role::findByName('user', 'api');

        $user->assignRole($defaultRole);

        return $this->sendResponse([
            'role' => $defaultRole,
            'user' => $user,
        ], 'Role removed from user successfully.');
    }

    /**
     * Assign role to permission
     *
     * @param  Request  $request
     */
    public function assignPermissionToRole(Request $request)
    {
        $role = Role::findByName($request->assign['role']['name'], $request->assign['role']['guard_name']);
        $permission = Permission::findByName($request->assign['permission']['name'], $request->assign['permission']['guard_name']);

        $role->givePermissionTo($permission);

        return $this->sendResponse([
            'role' => $role,
            'permission' => $permission,
        ], 'Permission assigned to role successfully.');
    }

    /**
     * Remove permission from role
     *
     * @param  Request  $request
     */
    public function removePermissionFromRole(Request $request)
    {
        $role = Role::findByName($request->assign['role']['name'], $request->assign['role']['guard_name']);
        $permission = Permission::findByName($request->assign['permission']['name'], $request->assign['permission']['guard_name']);

        $role->revokePermissionTo($permission);

        return $this->sendResponse([
            'role' => $role,
            'permission' => $permission,
        ], 'Permission removed from role successfully.');
    }

    /**
     * Summary of assignPermissionToUser
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function assignPermissionToUser(Request $request)
    {
        $user = User::find($request->assign['user_id']);
        $permission = Permission::findByName($request->assign['permission']['name'], $request->assign['permission']['guard_name']);

        $user->givePermissionTo($permission);

        return $this->sendResponse([
            'user' => $user,
            'permission' => $permission,
        ], 'Permission assigned to user successfully.');
    }

    /**
     * Summary of removePermissionFromUser
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function revokePermissionFromUser(Request $request)
    {
        $user = User::find($request->assign['user_id']);
        $permission = Permission::findByName($request->assign['permission']['name'], $request->assign['permission']['guard_name']);

        $user->revokePermissionTo($permission);

        return $this->sendResponse([
            'user' => $user,
            'permission' => $permission,
        ], 'Permission removed from user successfully.');
    }

    /**
     * Summary of removePermissionFromRole
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function groupPermissions($permissions)
    {
        $groupedPermissions = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission['name']);
            $groupName = $parts[0];

            if (!isset($groupedPermissions[$groupName])) {
                $groupedPermissions[$groupName] = [
                    'title' => $groupName,
                    'permissions' => [],
                ];
            }

            $groupedPermissions[$groupName]['permissions'][] = $permission;
        }

        return $groupedPermissions;
    }
}
