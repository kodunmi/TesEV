<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tax_percentage',
        'min_extension_time_buffer',
        'subscriber_price_per_hour'
    ];
}
