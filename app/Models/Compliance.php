<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compliance extends Model
{
    use HasFactory;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'driver_license_front',
        'driver_license_back',
        'photo',
        'license_state',
        'poster_code',
        'license_number',
        'expiration_date',
        'user_id',
        'active',
        'license_verified',
        'license_verified_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'owner_id');
    }

    public function driverLicenseFront()
    {
        return $this->hasOne(File::class, 'owner_id')->where('type', 'driver_license_front');
    }
    public function driverLicenseBack()
    {
        return $this->hasOne(File::class, 'owner_id')->where('type', 'driver_license_back');
    }
    public function photo()
    {
        return $this->hasOne(File::class, 'owner_id')->where('type', 'photo');
    }
}
