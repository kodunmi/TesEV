<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'hours',
        'frequency',
        'status',
        'active',
        'public_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'file_upload' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(PackageUser::class)
            ->as('subscription')
            ->withTimestamps()
            ->withPivot([
                'id',
                'subscribed_at',
                'due_at',
                'unsubscribed_at',
                'frequency',
            ]);
    }
}
