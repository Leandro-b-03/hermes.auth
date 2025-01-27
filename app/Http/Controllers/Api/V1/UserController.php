<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\InviteUser;
use Spatie\Permission\Models\Permission;

use App\Http\Controllers\Api\V1\RolesPermissionController;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * Summary of index
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->can('auth.read')) {
            return $this->sendError('Unauthorized.', [], 403);
        }

        $users = $this->query(request());

        return $this->sendResponse(['users' => $users], 'Users retrieved successfully.');
    }

    /**
     * Summary of invite
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|mixed
     */
    public function invite(Request $request)
    {
        DB::beginTransaction();
        $errors = []; // Array to store validation errors

        // Adjust payload structure
        $users = collect($request->users)->map(function ($email) {
            return ['email' => $email];
        });

        foreach ($users as $user) {
            // Validate email
            $validator = Validator::make($user, [
                'email' => 'required|email|unique:invite_users,email',
            ]);

            // Generate token
            $token = Str::uuid()->toString();

            if ($validator->fails()) {
                $emailError = $validator->errors()->first('email');
                if ($emailError === 'validation.unique') {
                    $invitation = InviteUser::where('email', $user['email'])->first();

                    if (!$invitation->is_active) {
                        return $this->sendError('Validation failed.', ['Email already a member.'], JsonResponse::HTTP_BAD_REQUEST);
                    } else {
                        try {
                            $invitation->update([
                                'token' => $token,
                                'token_expired_at' => now()->addDays(7),
                                'invite_user_url' => route('verification.verify', ['id' => Auth::user()->id, 'hash' => $token]),
                                'is_active' => true,
                            ]);

                            // Send email notification with the token
                            // Mail::to($user['email'])->send(new InvitationEmail($token));

                            DB::commit();
                        } catch (\Exception $e) {
                            logger($e);
                            DB::rollBack();
                            $errors[$user['email']] = $validator->errors();
                        }
                    }
                } else {
                    $errors[$user['email']] = $validator->errors(); // Store errors for this user
                }
            } else {
                // Create invitation record
                $invitation = new InviteUser([
                    'user_id' => Auth::user()->id,
                    'shipper_id' => Auth::user()->shipper_id,
                    'email' => $user['email'],
                    'token' => $token,
                    'token_expired_at' => now()->addDays(7),
                    'invite_user_url' => route('verification.verify', ['id' => Auth::user()->id, 'hash' => $token]),
                    'is_active' => true,
                ]);

                try {
                    $invitation->saveOrFail();

                    // Send email notification with the token
                    // Mail::to($user['email'])->send(new InvitationEmail($token));

                } catch (\Exception $e) {
                    $errors[$user['email']] = [$e->getMessage()]; // Store error for this user
                }
            }
        }

        if (empty($errors)) {
            DB::commit();
            return $this->sendResponse([], 'Invitation sent successfully.');
        } else {
            DB::rollBack();
            return $this->sendError('Validation failed.', $errors, JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Summary of show
     * @param mixed $id
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->sendError('User not found.', [], 404);
        }

        if (!auth()->user()->hasRole('admin') && !auth()->user()->can('auth.read')) {
            return $this->sendError('Unauthorized.', [], 403);
        }

        return $this->sendResponse(['user' => $user], 'User retrieved successfully.');
    }

    /**
     * Summary of show
     * @param int $id
     * @return JsonResponse|mixed
     */
    public function delete($id)
    {
        DB::beginTransaction();

        $user = User::find($id);

        if (!$user) {
            return $this->sendError('User not found.', [], 404);
        }

        try {
            $user->active = false;
            $user->save();

            DB::commit();

            return $this->sendResponse([], 'User deactivated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to deactivate user.', [], 500);
        }
    }

    /**
     * Summary of reactive
     * @param mixed $id
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function reactive($id)
    {
        DB::beginTransaction();

        $user = User::find($id);

        if (!$user) {
            return $this->sendError('User not found.', [], 404);
        }

        try {
            $user->active = true;
            $user->save();

            DB::commit();

            return $this->sendResponse([], 'User deactivated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to deactivate user.', [], 500);
        }
    }

    /**
     * Query the database for users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $page
     * @return \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    protected function query(Request $request, $page = true)
    {
        $perPage = $request->input('per_page', 10);
        $orderBy = $request->input('order_by', 'desc');
        $filter = $request->input('filter');
        $fields = $request->input('fields');
        $menu = $request->input('menu');

        $query = User::whereHas('shipper', function ($q) use ($request) {
            $q->where('shipper_id', \Auth::user()->shipper_id);
        })->select('id', 'name', 'email', 'email_verified_at', 'active', 'created_at', 'updated_at');

        if ($filter && $fields) {
            $fields = (is_array($fields)) ? explode(',', $fields[0]) : [$fields];

            $query->where(function ($q) use ($filter, $fields) {
                foreach ($fields as $i => $field) {
                    foreach ($fields as $i => $field) {
                        // Split the field string if it contains a dot (for relationship fields)
                        $fieldParts = explode('.', $field);

                        if (count($fieldParts) === 1) { // Field belongs to user model
                            $whereClause = $i === 0 ? 'where' : 'orWhere';
                            $q->{$whereClause}($field, 'like', "%$filter%");
                        } else { // Field belongs to userContact model
                            $relation = $fieldParts[0];
                            $fieldName = $fieldParts[1]; // e.g., 'name'

                            $whereClause = $i === 0 ? 'whereHas' : 'orWhereHas';
                            $q->{$whereClause}($relation, function ($relationQuery) use ($fieldName, $filter) {
                                $relationQuery->where($fieldName, 'like', "%$filter%");
                            });
                        }
                    }
                }
            });
        }

        $query = $query->with('userInfo')->orderBy('created_at', $orderBy);

        if ($menu === 'permissions') {
            $permissions = Permission::where('guard_name', 'api')->get();
            $groupedPermissions = RolesPermissionController::groupPermissions($permissions);

            $query = $page ? $query->where('active', true)->with('roles')->with('permissions')->paginate($perPage) : $query->get();
        
            // Assuming $query is an Eloquent query builder instance you have built before
            $query->getCollection()->transform(function ($user) use ($groupedPermissions) {
                $userPermissions = optional($user->permissions)->pluck('name')->toArray() ?? []; // Handle cases where $user->permissions might be null
        
                $formattedPermissions = $user->formattedPermissions;
                foreach ($groupedPermissions as $groupData) {
                    foreach ($groupData["permissions"] as $perm) {
                        $formattedPermissions[$groupData['title']][$perm["name"]] = $userPermissions ? in_array($perm['name'], $userPermissions) : false;
                    }
                }        
                // $user->append["formattedPermissions"];
                $user->setFormattedPermissions($formattedPermissions);
                // $user->permissions = $formattedPermissions;

                return $user;
            });

            return $query;
        }

        return $page ? $query->paginate($perPage) : $query->get();
    }
}
