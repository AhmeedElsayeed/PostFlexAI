<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'value' => 'json'
    ];

    /**
     * Get the setting value with proper type casting.
     */
    public function getValueAttribute($value)
    {
        $value = json_decode($value, true);
        
        switch ($this->type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return is_array($value) ? $value : json_decode($value, true);
            default:
                return (string) $value;
        }
    }

    /**
     * Set the setting value with proper type casting.
     */
    public function setValueAttribute($value)
    {
        if ($this->type === 'array' || $this->type === 'json') {
            $value = is_array($value) ? $value : json_decode($value, true);
        }
        
        $this->attributes['value'] = json_encode($value);
    }

    /**
     * Scope a query to only include public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include settings from a specific group.
     */
    public function scopeFromGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get all settings as a key-value array.
     */
    public static function getSettingsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Get all public settings as a key-value array.
     */
    public static function getPublicSettingsArray(): array
    {
        return static::public()->get()->pluck('value', 'key')->toArray();
    }

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value, string $type = 'string', string $group = 'general', string $description = null, bool $isPublic = false): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic
            ]
        );
    }

    /**
     * Get the group name in Arabic.
     */
    public function getGroupName(): string
    {
        $groups = [
            'general' => 'عام',
            'registration' => 'التسجيل',
            'email' => 'البريد الإلكتروني',
            'policies' => 'السياسات',
            'features' => 'الميزات',
            'security' => 'الأمان',
            'notifications' => 'الإشعارات',
            'social' => 'التواصل الاجتماعي',
            'payment' => 'الدفع',
            'other' => 'أخرى'
        ];

        return $groups[$this->group] ?? $this->group;
    }

    /**
     * Get the type name in Arabic.
     */
    public function getTypeName(): string
    {
        $types = [
            'string' => 'نص',
            'boolean' => 'نعم/لا',
            'integer' => 'رقم صحيح',
            'float' => 'رقم عشري',
            'array' => 'مصفوفة',
            'json' => 'JSON',
            'other' => 'أخرى'
        ];

        return $types[$this->type] ?? $this->type;
    }
} 