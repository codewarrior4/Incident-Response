<?php

namespace Tests\Unit\Services;

use App\Models\Incident;
use App\Services\NormalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NormalizationService();
    }

    public function test_removes_timestamps(): void
    {
        $incident = Incident::factory()->make([
            'message' => 'Error at 2024-04-27 10:30:45: Connection failed',
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertStringNotContainsString('2024-04-27', $normalized);
        $this->assertStringNotContainsString('10:30:45', $normalized);
    }

    public function test_removes_file_paths(): void
    {
        $incident = Incident::factory()->make([
            'message' => 'Error in /var/www/app/Controllers/PaymentController.php on line 42',
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertStringNotContainsString('/var/www/app/Controllers/PaymentController.php', $normalized);
        $this->assertStringContainsString('[FILE_PATH]', $normalized);
    }

    public function test_removes_line_numbers(): void
    {
        $incident = Incident::factory()->make([
            'message' => 'Exception on line:123 Invalid argument',
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertStringNotContainsString(':123', $normalized);
    }

    public function test_removes_uuids(): void
    {
        $incident = Incident::factory()->make([
            'message' => 'User 550e8400-e29b-41d4-a716-446655440000 not found',
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertStringNotContainsString('550e8400-e29b-41d4-a716-446655440000', $normalized);
        $this->assertStringContainsString('[UUID]', $normalized);
    }

    public function test_removes_numeric_ids(): void
    {
        $incident = Incident::factory()->make([
            'message' => 'Order #12345 failed with user ID 67890',
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertStringNotContainsString('12345', $normalized);
        $this->assertStringNotContainsString('67890', $normalized);
        $this->assertStringContainsString('[ID]', $normalized);
    }

    public function test_normalizes_whitespace(): void
    {
        $incident = Incident::factory()->make([
            'message' => "Error:   multiple    spaces\n\nand\nnewlines",
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertStringNotContainsString('  ', $normalized);
        $this->assertStringNotContainsString("\n\n", $normalized);
    }

    public function test_trims_result(): void
    {
        $incident = Incident::factory()->make([
            'message' => '   Error message with leading and trailing spaces   ',
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertEquals(trim($normalized), $normalized);
        $this->assertStringStartsNotWith(' ', $normalized);
        $this->assertStringEndsNotWith(' ', $normalized);
    }

    public function test_normalization_is_idempotent(): void
    {
        $incident1 = Incident::factory()->make([
            'message' => 'Error at 2024-04-27 in /path/file.php:123 with ID 456',
        ]);

        $normalized1 = $this->service->normalize($incident1);

        $incident2 = Incident::factory()->make([
            'message' => $normalized1,
        ]);

        $normalized2 = $this->service->normalize($incident2);

        $this->assertEquals($normalized1, $normalized2);
    }

    public function test_preserves_core_error_message(): void
    {
        $incident = Incident::factory()->make([
            'message' => 'SQLSTATE[23000]: Integrity constraint violation at 2024-04-27',
        ]);

        $normalized = $this->service->normalize($incident);

        $this->assertStringContainsString('SQLSTATE', $normalized);
        $this->assertStringContainsString('Integrity constraint violation', $normalized);
        // Note: numeric codes like 23000 will be replaced with [ID]
        $this->assertStringContainsString('[ID]', $normalized);
    }
}
