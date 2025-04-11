<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'date_range_start',
        'date_range_end',
        'metrics',
        'format',
        'status',
        'file_path',
        'schedule',
        'recipients',
        'user_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metrics' => 'array',
        'recipients' => 'array',
        'date_range_start' => 'datetime',
        'date_range_end' => 'datetime'
    ];

    /**
     * Get the user that owns the report.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 