<?php

namespace Tests\Unit\Services;

use App\Models\Incident;
use App\Services\DeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeduplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DeduplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeduplicationService();
    }

    public function test_generate_hash_produces_sha256_hash(): void
    {
        $service = 'payments';
        $message = 'Database connection failed';

        $hash = $this->service->generateHash($service, $message);

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash)); // SHA-256 produces 64 character hex string
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_generate_hash_is_deterministic(): void
    {
        $service = 'payments';
        $message = 'Database connection failed';

        $hash1 = $this->service->generateHash($service, $message);
        $hash2 = $this->service->generateHash($service, $message);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_generate_hash_differs_for_different_inputs(): void
    {
        $hash1 = $this->service->generateHash('payments', 'Error A');
        $hash2 = $this->service->generateHash('payments', 'Error B');
        $hash3 = $this->service->generateHash('auth', 'Error A');

        $this->assertNotEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertNotEquals($hash2, $hash3);
    }

    public function test_find_existing_incident_returns_null_for_new_hash(): void
    {
        $hash = 'nonexistent_hash_12345';

        $incident = $this->service->findExistingIncident($hash);

        $this->assertNull($incident);
    }

    public function test_find_existing_incident_returns_incident_for_existing_hash(): void
    {
        $existingIncident = Incident::factory()->create([
            'hash' => 'existing_hash_12345',
        ]);

        $incident = $this->service->findExistingIncident('existing_hash_12345');

        $this->assertNotNull($incident);
        $this->assertInstanceOf(Incident::class, $incident);
        $this->assertEquals($existingIncident->id, $incident->id);
    }

    public function test_record_occurrence_creates_occurrence_record(): void
    {
        $incident = Incident::factory()->create();
        $context = [
            'user_id' => 123,
            'ip_address' => '192.168.1.1',
        ];

        $occurrence = $this->service->recordOccurrence($incident, $context);

        $this->assertNotNull($occurrence);
        $this->assertEquals($incident->id, $occurrence->incident_id);
        $this->assertEquals($context, $occurrence->context);
        $this->assertDatabaseHas('incident_occurrences', [
            'incident_id' => $incident->id,
        ]);
    }

    public function test_record_occurrence_with_null_context(): void
    {
        $incident = Incident::factory()->create();

        $occurrence = $this->service->recordOccurrence($incident, null);

        $this->assertNotNull($occurrence);
        $this->assertEquals($incident->id, $occurrence->incident_id);
        $this->assertNull($occurrence->context);
    }
}
