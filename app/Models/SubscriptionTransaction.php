<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionTransaction extends Model
{
    use HasFactory;
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'subscribed_by',
        'service_id',
        'subscription_id',
        'reference',
        'public_id',
    ];

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }
}
