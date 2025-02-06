<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipperModule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'shipper_module';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public function shipper()
    {
        return $this->belongsTo(Shipper::class);
    }

    /**
     * Get the module that owns the ShipperModule
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
