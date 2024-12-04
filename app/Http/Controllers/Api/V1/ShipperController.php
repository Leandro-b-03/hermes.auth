<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Shipper;

use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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
        $validator = $this->validate($request->all());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

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

        if ($shipper->tax_id !== $request->input('shipper')['tax_id']) {
            return $this->sendError('Tax ID cannot be updated.');
        }

        try {
            DB::beginTransaction();

            $logo = $request->file('shipper')['logo'] ?? null;

            $values = $request->input('shipper');

            foreach ($values as $key => $value) {
                if ($value === 'null') {
                    $values[$key] = null;
                }
            }

            if ($logo) {
                $s3Disk = Storage::disk('s3');

                $file = $s3Disk->putFileAs('logos', $logo, str_replace('/', '_', $shipper->tax_id) . "_" . str_replace(' ', '_', $logo->getClientOriginalName()));
                $values['logo_image_url'] = $s3Disk->url($file);                
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

    /**
     * Get the tax ID of the shipper.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function validate($request)
    {
        $validator = Validator::make($request, [
            'shipper' => 'required|array',
            'shipper.name' => 'required|string',
            'shipper.tax_id' => 'required|string',
            'shipper.commercial_name' => 'required|string',
            'shipper.address' => 'required|string',
            'shipper.address_2' => 'nullable|string',
            'shipper.address_3' => 'required|string',
            'shipper.number' => 'required|string',
            'shipper.city' => 'required|string',
            'shipper.state' => 'required|string',
            'shipper.country' => 'required|string',
            'shipper.zip_code' => 'required|string',
            'shipper.contact_name' => 'required|string',
            'shipper.contact_email' => 'required|email',
            'shipper.contact_title' => 'nullable|string',
            'shipper.contact_department' => 'nullable|string',
            'shipper.contact_phone' => 'nullable|string',
            'shipper.contact_mobile' => 'nullable|string',
            'shipper.contact_fax' => 'nullable|string',
            'shipper.contact_document' => 'nullable|string',
            'shipper.logo_image_url' => 'nullable|string',
            'shipper.shipper_matrix_id' => 'nullable',
        ]);

        return $validator;
    }
}
