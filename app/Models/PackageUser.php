<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PackageUser extends Pivot
{
    use HasUuids;

    protected $fillable = [
        'subscribed_at',
        'due_at',
        'unsubscribed_at',
        'balance',
        'frequency',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
