<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileRemoval extends Model
{
    protected $fillable = [
        'file_id',
        'old_path',
        'status',
        'error',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
}