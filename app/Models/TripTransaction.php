<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripTransaction extends Model
{
    use HasFactory;

    use HasUuids, SoftDeletes;

    protected $fillable = [
        'trip_id',
        'building_id',
        'vehicle_id',
        'user_id',
        'reference',
        'amount',
        'total_amount',
        'status',
        'public_id',
        'tax_amount',
        'tax_percentage',
    ];


    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }
}
