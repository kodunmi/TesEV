<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'number',
        'url',
        'size',
        'file_id',
        'provider',
        'public_id',
        'path',
        'extension',
        'folder',
        'owner_id'
    ];
}
