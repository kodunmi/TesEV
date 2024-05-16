<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'reference',
        'amount',
        'total_amount',
        'narration',
        'title',
        'status',
        'entry',
        'type',
        'public_id',
        'channel',
        'transaction_date',
        'meta',
        'object',
        'user_id'
    ];

    protected $casts = [
        'meta' => 'array',
        'object' => 'array'
    ];


    public function transactable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'transactable_type', 'transactable_id');
    }
}
