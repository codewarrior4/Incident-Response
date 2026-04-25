<?php

namespace Database\Seeders;

use App\Models\Incident;
use App\Models\IncidentAnalysis;
use App\Models\IncidentOccurrence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 50 incidents with varied services, severities, statuses
        $incidents = Incident::factory(50)->create();

        // Create analyses for all incidents (mix of AI and rule-based)
        foreach ($incidents as $incident) {
            if (fake()->boolean(30)) { // 30% AI-generated
                IncidentAnalysis::factory()->aiGenerated()->create([
                    'incident_id' => $incident->id,
                ]);
            } else {
                IncidentAnalysis::factory()->ruleBased()->create([
                    'incident_id' => $incident->id,
                ]);
            }
        }

        // Create 2-5 occurrences for 20 incidents to simulate recurring issues
        $recurringIncidents = $incidents->random(20);
        foreach ($recurringIncidents as $incident) {
            $occurrenceCount = fake()->numberBetween(2, 5);
            IncidentOccurrence::factory($occurrenceCount)->create([
                'incident_id' => $incident->id,
            ]);
        }
    }
}
