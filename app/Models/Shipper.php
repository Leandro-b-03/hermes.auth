<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipper extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tax_id',
        'name',
        'fantasy_name',
        'address',
        'address_2',
        'address_3',
        'number',
        'city',
        'state',
        'country',
        'zip_code',
        'contact_name',
        'contact_email',
        'contact_title',
        'contact_department',
        'contact_phone',
        'contact_mobile',
        'contact_fax',
        'contact_document',
        'logo_image_url',
        'shipper_matrix_id'
    ];

    /**
     * Get the users for the shipper.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
