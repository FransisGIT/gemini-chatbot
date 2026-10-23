<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'avatar',
        'is_otp',
        'otp_code',
        'otp_expires_at',
        'permission',
        'is_active',
        'is_email_notification_enabled',
        'is_whatsapp_notification_enabled',
        'active_device',
        'password',
        'password_changed_at',
    ];

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function mautCriteria(): HasMany
    {
        return $this->hasMany(MautCriterion::class);
    }

    public function mautAlternatives(): HasMany
    {
        return $this->hasMany(MautAlternative::class);
    }

    public function mautIndicators(): HasMany
    {
        return $this->hasMany(MautIndicator::class);
    }
}
