<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */

    'upload_to_cloud' => env('BACKUP_UPLOAD_TO_CLOUD', false),
    'cloud_disk' => env('BACKUP_CLOUD_DISK', 's3'),
    'retention_days' => env('BACKUP_RETENTION_DAYS', 7),
    'notification_email' => env('BACKUP_NOTIFICATION_EMAIL'),
]; 