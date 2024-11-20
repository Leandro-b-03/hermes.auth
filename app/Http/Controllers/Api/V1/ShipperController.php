<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Shipper;

use App\Http\Controllers\Api\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

    public function getUserShipper()
    {
        $user = auth()->user();
        $shipper = Shipper::find($user['shipper_id']);

        if (!$shipper) {
            return $this->sendError('Shipper not found.');
        }

        return $this->sendResponse(['shipper' => $shipper], 'Shipper retrieved successfully.');
    }
}
