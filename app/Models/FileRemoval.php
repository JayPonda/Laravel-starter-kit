<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileRemoval extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'file_id',
        'disk',
        'old_path',
        'status',
        'error',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
}