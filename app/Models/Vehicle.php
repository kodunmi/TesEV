<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'status',
        'price_per_hour',
        'image',
        'plate_number',
        'building_id',
        'public_id',

        'battery_capacity', // The capacity of the vehicle's battery (e.g., in kWh)
        'charging_time', // The time required to fully charge the battery (e.g., in hours)
        'range', // The estimated range of the vehicle on a single charge (e.g., in miles or kilometers)
        'power_output', // The power output of the vehicle's electric motor (e.g., in kW)
        'acceleration', // The acceleration performance of the vehicle (e.g., 0-60 mph time)
        'charging_connector_type', // The type of charging connector used by the vehicle (e.g., Type 2, CCS, CHAdeMO)
        'energy_efficiency', // The energy efficiency rating of the vehicle (e.g., in miles/kWh or kilometers/kWh)
        'charging_network', // The supported charging network(s) for the vehicle (e.g., Tesla Supercharger, Electrify America)
        'battery_warranty', // The warranty coverage for the vehicle's battery (e.g., in years or miles/kilometers)
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
