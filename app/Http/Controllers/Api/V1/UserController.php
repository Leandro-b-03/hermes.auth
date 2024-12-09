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

        $users = $this->query(request());

        return $this->sendResponse(['users' => $users], 'Users retrieved successfully.');
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

        return $page ? $query->paginate($perPage) : $query->get();
    }
}
