<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = ['api', 'admin', 'super_admin'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'shipper_id',
        'email_verified_at',
    ];

 /**
     * The attributes that should be appended to the model.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formatted_permissions', // Use snake_case for appends
    ];

    /**
     * The property to store formatted permissions.
     *
     * @var array
     */
    protected $formattedPermissions = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user info associated with the user.
     */
    public function userInfo()
    {
        return $this->hasOne(UserInfo::class);
    }

    /**
     * Get the shipper that owns the user.
     */
    public function shipper()
    {
        return $this->belongsTo(Shipper::class);
    }

    /**
     * Get the user's formatted permissions.
     *
     * @return array
     */
    public function getFormattedPermissionsAttribute()
    {
        return $this->formattedPermissions;
    }

    /**
     * Set the user's formatted permissions.
     *
     * @param array $formattedPermissions
     * @return void
     */
    public function setFormattedPermissions(array $formattedPermissions)
    {
        $this->formattedPermissions = $formattedPermissions;
    }
}
