<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    use HasFactory;

    /*
    * Return the user that owns the user info
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
