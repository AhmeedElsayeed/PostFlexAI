<?php

namespace App\Console\Commands;

use App\Services\MessageReminderService;
use Illuminate\Console\Command;

class ProcessMessageReminders extends Command
{
    protected $signature = 'messages:process-reminders';
    protected $description = 'Process pending message reminders and send notifications';

    protected MessageReminderService $reminderService;

    public function __construct(MessageReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle(): void
    {
        $this->info('Processing message reminders...');
        
        $this->reminderService->processReminders();
        
        $this->info('Message reminders processed successfully.');
    }
} 