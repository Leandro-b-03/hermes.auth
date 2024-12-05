<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;

use App\Models\User;

class UserController extends BaseController
{
    /**
     * Summary of index
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index() {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->can('auth.read')) {
            return $this->sendError('Unauthorized.', [], 403);
        }

        $users = User::where('shipper_id', \Auth::user()->shipper_id)->select('id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at')->get();

        return $this->sendResponse(['users' => $users], 'Users retrieved successfully.');
    }
}
