<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmartReplyTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'content',
        'keywords',
        'category',
        'success_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'keywords' => 'array',
        'success_rate' => 'float',
    ];

    /**
     * Get the validation rules for the model.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'keywords' => 'required|array',
            'keywords.*' => 'string',
            'category' => 'required|string|max:50',
            'success_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
} 