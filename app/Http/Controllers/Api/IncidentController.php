<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreIncidentRequest;
use App\Http\Requests\Api\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Jobs\IncidentCreatedJob;
use App\Models\Incident;
use App\Services\DeduplicationService;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct(
        private DeduplicationService $deduplicationService
    ) {}

    public function store(StoreIncidentRequest $request)
    {
        $validated = $request->validated();

        // Generate hash for deduplication
        $hash = $this->deduplicationService->generateHash(
            $validated['service'],
            $validated['message']
        );

        // Check for existing incident
        $existingIncident = $this->deduplicationService->findExistingIncident($hash);

        if ($existingIncident) {
            // Record occurrence
            $this->deduplicationService->recordOccurrence(
                $existingIncident,
                $validated['context'] ?? null
            );

            return new IncidentResource($existingIncident);
        }

        // Create new incident
        $incident = Incident::create([
            'title' => substr($validated['message'], 0, 100),
            'message' => $validated['message'],
            'service' => $validated['service'],
            'hash' => $hash,
            'severity' => 'medium', // Will be updated by analysis
            'status' => 'open',
        ]);

        // Dispatch job for async processing
        IncidentCreatedJob::dispatch($incident);

        return new IncidentResource($incident);
    }

    public function index(Request $request)
    {
        $query = Incident::query()
            ->with(['analysis', 'occurrences'])
            ->withCount('occurrences');

        // Apply filters
        if ($request->filled('service')) {
            $query->where('service', $request->service);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $incidents = $query->latest()->paginate(25);

        return IncidentResource::collection($incidents);
    }

    public function show(Incident $incident)
    {
        $incident->load(['analysis', 'occurrences' => fn ($q) => $q->latest()]);

        return new IncidentResource($incident);
    }

    public function update(UpdateIncidentRequest $request, Incident $incident)
    {
        $incident->update($request->validated());

        return new IncidentResource($incident);
    }
}
