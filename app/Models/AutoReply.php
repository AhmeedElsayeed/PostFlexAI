<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'trigger_keyword',
        'response_text',
        'platform'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
} 