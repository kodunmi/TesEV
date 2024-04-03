<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Token extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'token',
        'purpose',
        'recipient',
        'data',
        'channel',
        'ttl',
        'verified_at',
        'valid',
        'public_id',
        'expired_at',
        'meta',
    ];

    protected $casts = [
        'valid' => 'boolean',
        'meta' => 'array',
    ];
}
