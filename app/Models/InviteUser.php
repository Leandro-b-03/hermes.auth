<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InviteUser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'shipper_id',
        'email',
        'token',
        'invite_user_url',
        'email_verified_at',
        'token_expired_at',
        'invite_user_expired_at',
        'is_active',
    ];
}
