<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Create a backup of the database';

    public function handle()
    {
        $filename = 'backup-' . Carbon::now()->format('Y-m-d-H-i-s') . '.sql';
        $path = 'backups/' . $filename;

        // Create backup directory if it doesn't exist
        if (!Storage::exists('backups')) {
            Storage::makeDirectory('backups');
        }

        // Get database configuration
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        // Create backup command
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            $host,
            $username,
            $password,
            $database,
            storage_path('app/' . $path)
        );

        // Execute backup
        exec($command);

        // Upload to cloud storage if configured
        if (config('backup.upload_to_cloud')) {
            $this->uploadToCloud($path);
        }

        // Clean up old backups
        $this->cleanupOldBackups();

        $this->info('Database backup created successfully: ' . $filename);
    }

    protected function uploadToCloud($path)
    {
        $disk = Storage::disk(config('backup.cloud_disk'));
        $disk->putFileAs(
            'backups',
            storage_path('app/' . $path),
            basename($path)
        );
    }

    protected function cleanupOldBackups()
    {
        $files = Storage::files('backups');
        $now = Carbon::now();

        foreach ($files as $file) {
            $timestamp = Carbon::createFromFormat(
                'Y-m-d-H-i-s',
                substr(basename($file), 7, 19)
            );

            if ($now->diffInDays($timestamp) > config('backup.retention_days', 7)) {
                Storage::delete($file);
            }
        }
    }
} 