<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentAnalysis>
 */
class IncidentAnalysisFactory extends Factory
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
            'root_cause' => fake()->sentence(),
            'suggested_fix' => fake()->sentence(),
            'confidence_score' => fake()->numberBetween(60, 95),
            'ai_generated' => fake()->boolean(),
        ];
    }

    public function aiGenerated(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_generated' => true,
            'confidence_score' => fake()->numberBetween(80, 95),
        ]);
    }

    public function ruleBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_generated' => false,
            'confidence_score' => fake()->numberBetween(60, 80),
        ]);
    }
}
