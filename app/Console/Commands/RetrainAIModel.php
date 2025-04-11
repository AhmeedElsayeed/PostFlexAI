<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AIModelFeedback;
use App\Services\AIAudienceAnalysisService;
use Illuminate\Support\Facades\Log;

class RetrainAIModel extends Command
{
    protected $signature = 'ai:retrain {model_type?}';
    protected $description = 'Retrain AI models based on user feedback';

    protected $aiService;

    public function __construct(AIAudienceAnalysisService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    public function handle()
    {
        $modelType = $this->argument('model_type');

        try {
            $this->info('Starting AI model retraining...');

            // Get feedback data
            $query = AIModelFeedback::where('is_resolved', true);
            if ($modelType) {
                $query->where('model_type', $modelType);
            }
            $feedback = $query->get();

            if ($feedback->isEmpty()) {
                $this->info('No feedback data available for retraining.');
                return;
            }

            // Process feedback and update model
            $this->aiService->processFeedback($feedback);

            // Log successful retraining
            Log::info('AI model retraining completed successfully', [
                'model_type' => $modelType ?? 'all',
                'feedback_count' => $feedback->count()
            ]);

            $this->info('AI model retraining completed successfully.');
        } catch (\Exception $e) {
            Log::error('AI model retraining failed', [
                'error' => $e->getMessage(),
                'model_type' => $modelType ?? 'all'
            ]);

            $this->error('AI model retraining failed: ' . $e->getMessage());
        }
    }
} 