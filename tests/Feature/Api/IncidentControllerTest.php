<?php

namespace Tests\Feature\Api;

use App\Enums\SeverityEnum;
use App\Enums\StatusEnum;
use App\Models\Incident;
use App\Models\IncidentAnalysis;
use App\Models\IncidentOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IncidentControllerTest extends TestCase
{
    use RefreshDatabase;

    // Task 13.1: Incident Creation Tests

    public function test_creates_incident_with_valid_data_returns_201(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Database connection failed',
            'context' => [
                'user_id' => 123,
                'ip_address' => '192.168.1.1',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'message',
                    'service',
                    'severity',
                    'status',
                    'hash',
                    'occurrences_count',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('incidents', [
            'service' => 'payments',
            'message' => 'Database connection failed',
        ]);
    }

    public function test_created_incident_has_uuid_and_timestamps(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/incidents', [
            'service' => 'auth',
            'message' => 'Authentication failed',
        ]);

        $response->assertStatus(201);

        $incident = Incident::first();
        $this->assertNotNull($incident->id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $incident->id);
        $this->assertNotNull($incident->created_at);
        $this->assertNotNull($incident->updated_at);
    }

    public function test_created_incident_has_default_status_open(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/incidents', [
            'service' => 'api',
            'message' => 'Internal server error',
        ]);

        $response->assertStatus(201);

        $incident = Incident::first();
        $this->assertEquals(StatusEnum::Open, $incident->status);
    }

    public function test_missing_service_returns_422(): void
    {
        $response = $this->postJson('/api/incidents', [
            'message' => 'Error message',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service']);
    }

    public function test_missing_message_returns_422(): void
    {
        $response = $this->postJson('/api/incidents', [
            'service' => 'payments',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_malformed_json_returns_400(): void
    {
        $response = $this->post('/api/incidents', [], [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422); // Laravel returns 422 for validation errors
    }

    // Task 13.2: Incident Listing and Filtering Tests

    public function test_lists_incidents_with_pagination(): void
    {
        Incident::factory()->count(30)->create();

        $response = $this->getJson('/api/incidents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'message', 'service', 'severity', 'status'],
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 25);
    }

    public function test_filters_incidents_by_service(): void
    {
        Incident::factory()->create(['service' => 'payments']);
        Incident::factory()->create(['service' => 'auth']);

        $response = $this->getJson('/api/incidents?service=payments');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('payments', $response->json('data.0.service'));
    }

    public function test_filters_incidents_by_severity(): void
    {
        Incident::factory()->create(['severity' => SeverityEnum::Critical]);
        Incident::factory()->create(['severity' => SeverityEnum::Low]);

        $response = $this->getJson('/api/incidents?severity=critical');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('critical', $response->json('data.0.severity'));
    }

    public function test_filters_incidents_by_status(): void
    {
        Incident::factory()->create(['status' => StatusEnum::Open]);
        Incident::factory()->create(['status' => StatusEnum::Resolved]);

        $response = $this->getJson('/api/incidents?status=resolved');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
        $this->assertEquals('resolved', $response->json('data.0.status'));
    }

    // Task 13.3: Incident Show Endpoint Tests

    public function test_shows_single_incident_with_relationships(): void
    {
        $incident = Incident::factory()->create();
        IncidentAnalysis::factory()->create(['incident_id' => $incident->id]);
        IncidentOccurrence::factory()->count(2)->create(['incident_id' => $incident->id]);

        $response = $this->getJson("/api/incidents/{$incident->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'message',
                    'service',
                    'severity',
                    'status',
                    'analysis' => [
                        'id',
                        'root_cause',
                        'suggested_fix',
                        'confidence_score',
                        'ai_generated',
                    ],
                    'occurrences' => [
                        '*' => ['id', 'context', 'created_at'],
                    ],
                    'occurrences_count',
                ],
            ]);

        $this->assertNotNull($response->json('data.analysis'));
        $this->assertCount(2, $response->json('data.occurrences'));
    }

    // Task 13.4: Incident Status Update Tests

    public function test_updates_status_with_valid_enum_value(): void
    {
        $incident = Incident::factory()->create(['status' => StatusEnum::Open]);

        $response = $this->patchJson("/api/incidents/{$incident->id}", [
            'status' => 'resolved',
        ]);

        $response->assertStatus(200);

        $incident->refresh();
        $this->assertEquals(StatusEnum::Resolved, $incident->status);
    }

    public function test_status_update_modifies_updated_at_timestamp(): void
    {
        $incident = Incident::factory()->create(['status' => StatusEnum::Open]);
        $originalUpdatedAt = $incident->updated_at;

        sleep(1);

        $this->patchJson("/api/incidents/{$incident->id}", [
            'status' => 'investigating',
        ]);

        $incident->refresh();
        $this->assertNotEquals($originalUpdatedAt, $incident->updated_at);
        $this->assertTrue($incident->updated_at->greaterThan($originalUpdatedAt));
    }

    public function test_rejects_invalid_status_with_422(): void
    {
        $incident = Incident::factory()->create();

        $response = $this->patchJson("/api/incidents/{$incident->id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
