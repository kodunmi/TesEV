<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripMetaData extends Model
{
    use HasFactory;
    use SoftDeletes, HasUuids;


    protected $fillable = [
        'trip_id',
        'public_id',
        'distance_covered',
        'remove_belongings',
        'remove_trash',
        'plug_vehicle'
    ];
}
