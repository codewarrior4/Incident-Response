<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentOccurrence;

class DeduplicationService
{
    public function generateHash(string $service, string $message): string
    {
        return hash('sha256', $service . $message);
    }

    public function findExistingIncident(string $hash): ?Incident
    {
        return Incident::where('hash', $hash)->first();
    }

    public function recordOccurrence(Incident $incident, ?array $context): IncidentOccurrence
    {
        return $incident->occurrences()->create([
            'context' => $context,
        ]);
    }
}
