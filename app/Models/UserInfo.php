<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;

    /**
     * Summary of fillable
     * @var array
     */
    protected $fillable = [
        'user_id',
        'photo_url',
        'phone',
        'document',
        'address',
        'address_2',
        'city',
        'state',
        'country',
        'zip_code'
    ];

    /**
     * Summary of guarded
     * @var array
     */
    protected $guarded = ['id'];

    /*
    * Return the user that owns the user info
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
