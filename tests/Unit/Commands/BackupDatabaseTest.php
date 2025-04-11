<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Console\Commands\BackupDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BackupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new BackupDatabase();
        Storage::fake('local');
    }

    public function test_creates_backup_directory()
    {
        $this->command->handle();
        
        $this->assertTrue(Storage::exists('backups'));
    }

    public function test_generates_backup_file()
    {
        $this->command->handle();
        
        $files = Storage::files('backups');
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('backups/backup-', $files[0]);
    }

    public function test_cleans_up_old_backups()
    {
        // Create old backup files
        Storage::put('backups/backup-2024-03-20-00-00-00.sql', 'old backup');
        Storage::put('backups/backup-2024-03-19-00-00-00.sql', 'older backup');
        
        $this->command->handle();
        
        $files = Storage::files('backups');
        $this->assertCount(1, $files); // Only the new backup should remain
    }

    public function test_uploads_to_cloud_when_configured()
    {
        config(['backup.upload_to_cloud' => true]);
        Storage::fake('s3');
        
        $this->command->handle();
        
        $files = Storage::disk('s3')->files('backups');
        $this->assertCount(1, $files);
    }
} 