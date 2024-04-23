<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasUuids, SoftDeletes, HasApiTokens;

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
        'customer_id'
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
        ];
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


    public function reports()
    {
        return $this->hasManyThrough(Report::class, Trip::class);
    }

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)->using(PackageUser::class)
            ->as('subscription')
            ->withTimestamps()
            ->withPivot([
                'id',
                'subscribed_at',
                'due_at',
                'unsubscribed_at',
                'balance'
            ])
            ->wherePivotNull('unsubscribed_at');
    }

    public function activeSubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)
            ->using(PackageUser::class)
            ->as('subscription')
            ->withTimestamps()
            ->withPivot([
                'id',
                'subscribed_at',
                'due_at',
                'unsubscribed_at',
                'balance',
            ])
            ->wherePivot('due_at', '>', now())
            ->wherePivotNull('unsubscribed_at');
    }

    public function unsubscribeSubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)
            ->using(PackageUser::class)
            ->as('subscription')
            ->withTimestamps()
            ->withPivot([
                'id',
                'subscribed_at',
                'due_at',
                'unsubscribed_at',
                'frequency',
                'auto_renew',
                'payment_mode',
                'balance'
            ])
            ->wherePivotNotNull('unsubscribed_at');
    }

    public function expiredSubscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)
            ->using(PackageUser::class)
            ->as('subscription')
            ->withTimestamps()
            ->withPivot([
                'id',
                'subscribed_at',
                'due_at',
                'unsubscribed_at',
                'frequency',
                'auto_renew',
                'payment_mode',
                'balance'
            ])
            ->wherePivot('due_at', '<', now()->toDateTimeString())
            ->wherePivotNull('unsubscribed_at');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function activeCard(): HasOne
    {
        return $this->hasOne(Card::class)->where('is_active', true);
    }
}
