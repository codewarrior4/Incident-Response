<?php

namespace Database\Factories;

use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'service' => fake()->randomElement(['auth', 'payments', 'api', 'notifications']),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'hash' => hash('sha256', fake()->unique()->uuid()),
            'status' => fake()->randomElement(['open', 'investigating', 'resolved']),
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
        ]);
    }
}
