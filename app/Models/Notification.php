<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'preview',
        'channel',
        'url',
        'is_read',
        'show',
        'read_by',
        'read_at',
        'sent_by',
        'type',
        'markup_body',
        'meta',
        'data',
        'attachments',
        'public_id',
    ];

    protected $casts = [
        'read_by' => 'array',
        'data' => 'array',
        'attachments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
