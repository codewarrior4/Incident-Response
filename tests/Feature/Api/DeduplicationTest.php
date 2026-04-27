<?php

namespace Tests\Feature\Api;

use App\Models\Incident;
use App\Services\DeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected DeduplicationService $deduplicationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deduplicationService = new DeduplicationService();
    }

    public function test_creates_new_incident_for_unique_hash(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Unique error message',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('incidents', 1);
        $this->assertDatabaseCount('incident_occurrences', 0);
    }

    public function test_creates_occurrence_for_duplicate_hash(): void
    {
        Queue::fake();

        // Create first incident
        $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Database connection failed',
        ]);

        // Submit duplicate
        $response = $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Database connection failed',
        ]);

        $response->assertStatus(200); // Returns 200 for duplicate

        $this->assertDatabaseCount('incidents', 1);
        $this->assertDatabaseCount('incident_occurrences', 1);
    }

    public function test_returns_200_for_duplicate_incident(): void
    {
        Queue::fake();

        $this->postJson('/api/incidents', [
            'service' => 'auth',
            'message' => 'Authentication failed',
        ]);

        $response = $this->postJson('/api/incidents', [
            'service' => 'auth',
            'message' => 'Authentication failed',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'message',
                    'service',
                    'occurrences_count',
                ],
            ]);
    }

    public function test_occurrence_contains_context_and_timestamp(): void
    {
        Queue::fake();

        $this->postJson('/api/incidents', [
            'service' => 'api',
            'message' => 'Internal server error',
            'context' => ['user_id' => 123],
        ]);

        $this->postJson('/api/incidents', [
            'service' => 'api',
            'message' => 'Internal server error',
            'context' => ['user_id' => 456, 'ip' => '192.168.1.1'],
        ]);

        $occurrence = \App\Models\IncidentOccurrence::first();

        $this->assertNotNull($occurrence);
        $this->assertIsArray($occurrence->context);
        $this->assertEquals(456, $occurrence->context['user_id']);
        $this->assertEquals('192.168.1.1', $occurrence->context['ip']);
        $this->assertNotNull($occurrence->created_at);
    }

    public function test_hash_generation_is_deterministic(): void
    {
        $hash1 = $this->deduplicationService->generateHash('payments', 'Error message');
        $hash2 = $this->deduplicationService->generateHash('payments', 'Error message');

        $this->assertEquals($hash1, $hash2);
    }

    public function test_unique_constraint_on_hash_column(): void
    {
        Queue::fake();

        $hash = $this->deduplicationService->generateHash('payments', 'Test error');

        Incident::factory()->create([
            'service' => 'payments',
            'message' => 'Test error',
            'hash' => $hash,
        ]);

        // Attempting to create another incident with the same hash should be handled by deduplication logic
        $response = $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Test error',
        ]);

        $response->assertStatus(200); // Should return existing incident
        $this->assertDatabaseCount('incidents', 1);
    }

    public function test_handles_concurrent_duplicate_submissions(): void
    {
        Queue::fake();

        // Simulate concurrent requests by creating the same incident twice rapidly
        $responses = [];
        for ($i = 0; $i < 2; $i++) {
            $responses[] = $this->postJson('/api/incidents', [
                'service' => 'concurrent',
                'message' => 'Concurrent error test',
            ]);
        }

        // One should be 201 (created), one should be 200 (duplicate)
        $statuses = array_map(fn($r) => $r->status(), $responses);

        $this->assertContains(201, $statuses);
        $this->assertContains(200, $statuses);
        $this->assertDatabaseCount('incidents', 1);
    }

    public function test_different_services_same_message_creates_separate_incidents(): void
    {
        Queue::fake();

        $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Connection timeout',
        ]);

        $this->postJson('/api/incidents', [
            'service' => 'auth',
            'message' => 'Connection timeout',
        ]);

        $this->assertDatabaseCount('incidents', 2);
        $this->assertDatabaseCount('incident_occurrences', 0);
    }

    public function test_same_service_different_messages_creates_separate_incidents(): void
    {
        Queue::fake();

        $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Error A',
        ]);

        $this->postJson('/api/incidents', [
            'service' => 'payments',
            'message' => 'Error B',
        ]);

        $this->assertDatabaseCount('incidents', 2);
        $this->assertDatabaseCount('incident_occurrences', 0);
    }

    public function test_multiple_occurrences_increment_count(): void
    {
        Queue::fake();

        // Create original incident
        $response1 = $this->postJson('/api/incidents', [
            'service' => 'api',
            'message' => 'Repeated error',
        ]);

        $incidentId = $response1->json('data.id');

        // Create 3 more occurrences
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/incidents', [
                'service' => 'api',
                'message' => 'Repeated error',
            ]);
        }

        $incident = Incident::withCount('occurrences')->find($incidentId);

        // 3 occurrences were created (in addition to the original incident)
        $this->assertEquals(3, $incident->occurrences()->count());
        // The accessor adds 1 to withCount, so 3 + 1 = 4 total
        $this->assertEquals(4, $incident->occurrences_count);
    }
}
