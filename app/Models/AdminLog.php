<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array'
    ];

    /**
     * Get the admin user who performed the action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the entity that was affected.
     */
    public function entity()
    {
        if ($this->entity_type && $this->entity_id) {
            return $this->entity_type::find($this->entity_id);
        }
        return null;
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific entity type.
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope a query to only include logs for a specific admin.
     */
    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Get the action name in Arabic.
     */
    public function getActionName(): string
    {
        $actions = [
            'create' => 'إنشاء',
            'update' => 'تحديث',
            'delete' => 'حذف',
            'restore' => 'استعادة',
            'suspend' => 'تعليق',
            'activate' => 'تفعيل',
            'change_role' => 'تغيير الصلاحية',
            'change_plan' => 'تغيير الباقة',
            'change_settings' => 'تغيير الإعدادات',
            'export_data' => 'تصدير البيانات',
            'import_data' => 'استيراد البيانات',
            'backup' => 'نسخ احتياطي',
            'restore_backup' => 'استعادة النسخة الاحتياطية',
            'clear_cache' => 'مسح الذاكرة المؤقتة',
            'run_migration' => 'تشغيل الترحيل',
            'run_seed' => 'تشغيل البذور',
            'run_command' => 'تشغيل أمر',
            'other' => 'أخرى'
        ];

        return $actions[$this->action] ?? $this->action;
    }

    /**
     * Get the entity type name in Arabic.
     */
    public function getEntityTypeName(): string
    {
        $types = [
            'User' => 'مستخدم',
            'Team' => 'فريق',
            'Subscription' => 'اشتراك',
            'Plan' => 'باقة',
            'Role' => 'صلاحية',
            'Permission' => 'إذن',
            'Setting' => 'إعداد',
            'Feedback' => 'ملاحظة',
            'Log' => 'سجل',
            'Backup' => 'نسخة احتياطية',
            'other' => 'أخرى'
        ];

        return $types[$this->entity_type] ?? $this->entity_type;
    }
} 