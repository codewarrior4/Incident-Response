<?php

namespace App\Jobs;

use App\Enums\StatusEnum;
use App\Models\Incident;
use App\Services\AnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyzeIncidentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 35; // Slightly longer than Ollama timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Incident $incident,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AnalysisService $analysisService): void
    {
        // Skip if already analyzed
        if ($this->incident->analysis()->exists()) {
            return;
        }

        $analysisService->analyze($this->incident);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('AnalyzeIncidentJob failed', [
            'incident_id' => $this->incident->id,
            'error' => $exception?->getMessage(),
        ]);

        // Ensure incident remains in open status
        $this->incident->update(['status' => StatusEnum::Open]);
    }
}
