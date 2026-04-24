<?php

namespace App\Jobs;

use App\Models\Incident;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class IncidentCreatedJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60; // Exponential backoff starting at 60 seconds

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Incident $incident,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Dispatch analysis job
        AnalyzeIncidentJob::dispatch($this->incident)
            ->onQueue(config('incident-intelligence.queue_connection'));
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('IncidentCreatedJob failed', [
            'incident_id' => $this->incident->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
