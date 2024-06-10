<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasUuids, SoftDeletes, HasApiTokens;
    use Billable;

    protected $keyType = 'string';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_code',
        'password',
        'email_verified_at',
        'wallet',
        'subscription_balance',
        'fcm_token'
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet' => 'double',
            'subscription_balance' => 'double',
        ];
    }

    /**
     * Specifies the user's FCM token
     *
     * @return string|array
     */
    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }


    public function compliances(): HasMany
    {
        return $this->hasMany(Compliance::class);
    }

    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class)->using(BuildingUser::class)->withPivot(['status']);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(File::class, 'owner_id');
    }

    public function reports()
    {
        return $this->hasManyThrough(Report::class, Trip::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function activeCard(): HasOne
    {
        return $this->hasOne(Card::class)->where('is_default', true);
    }
}
