<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Shipper;

use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipperController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->sendResponse([], 'Shipper retrieved successfully.');
    }

    /**
     * Get the tax ID of the shipper.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function taxId($id)
    {
        $shipper = Shipper::find($id);

        if (!$shipper) {
            return $this->sendError('Shipper not found.');
        }

        return $this->sendResponse(['tax_id' => $shipper['tax_id']], 'Tax ID retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getUserShipper()
    {
        $user = auth()->user();
        $shipper = Shipper::find($user['shipper_id']);

        if (!$shipper) {
            return $this->sendError('Shipper not found.');
        }

        return $this->sendResponse(['shipper' => $shipper], 'Shipper retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            return $this->sendError('Unauthorized.', [], 403);
        }

        $shipper = Shipper::find($id);

        if (!$shipper) {
            return $this->sendError('Shipper not found.');
        }

        if ($request->all() === []) {
            return $this->sendError('No data to update.');
        }

        try {
            DB::beginTransaction();
            
            $values = $request->input('shipper');

            foreach ($values as $key => $value) {
                if ($value === 'null') {
                    $values[$key] = null;
                }
            }

            $updated = $shipper->update($values);

            if (!$updated) {
                DB::rollBack();
                return $this->sendError('Shipper could not be updated.');
            }

            $shipper = Shipper::find($id);

            DB::commit();

            return $this->sendResponse(['shipper' => $shipper], 'Shipper updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            logger($e);
            return $this->sendError('Shipper could not be updated.');
        }
    }
}
