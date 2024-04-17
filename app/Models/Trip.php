<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory;
    use SoftDeletes, HasUuids;


    protected $fillable = [
        'user_id',
        'vehicle_id',
        'start_time',
        'end_time',
        'public_id',
        'booking_id',
        'parent_trip_id',
        'tax_amount',
        'tax_percentage',
        'status'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(Trip::class, 'parent_trip_id', 'id');
    }

    public function parentTrip(): BelongsTo
    {
        return $this->belongsTo(Trip::class, 'parent_trip_id', 'id');
    }

    public function tripMetaData(): HasOne
    {
        return $this->hasOne(TripMetaData::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
