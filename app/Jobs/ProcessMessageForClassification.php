<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\MessageClassificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMessageForClassification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function handle(MessageClassificationService $classificationService): void
    {
        $classificationService->classifyMessage($this->message);
    }
} 