<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentOccurrence>
 */
class IncidentOccurrenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'context' => [
                'user_id' => fake()->uuid(),
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
        ];
    }
}
