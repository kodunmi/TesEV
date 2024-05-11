<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'stripe_id',
        'last_four',
        'exp_year',
        'exp_month',
        'number',
        'is_default',
        'is_active',
        'public_id',
        'object',
    ];

    protected function casts(): array
    {
        return [
            'object' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
