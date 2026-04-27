<?php

namespace Tests\Unit\Services;

use App\Enums\ErrorTypeEnum;
use App\Enums\SeverityEnum;
use App\Services\ClassificationService;
use Tests\TestCase;

class ClassificationServiceTest extends TestCase
{
    protected ClassificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClassificationService();
    }

    // Severity Classification Tests

    public function test_classifies_sqlstate_as_critical(): void
    {
        $severity = $this->service->classifySeverity('SQLSTATE[23000]: Integrity constraint violation');

        $this->assertEquals(SeverityEnum::Critical, $severity);
    }

    public function test_classifies_out_of_memory_as_critical(): void
    {
        $severity = $this->service->classifySeverity('PHP Fatal error: Out of memory');

        $this->assertEquals(SeverityEnum::Critical, $severity);
    }

    public function test_classifies_memory_exhausted_as_critical(): void
    {
        $severity = $this->service->classifySeverity('PHP Fatal error: memory exhausted');

        $this->assertEquals(SeverityEnum::Critical, $severity);
    }

    public function test_classifies_timeout_as_high(): void
    {
        $severity = $this->service->classifySeverity('Connection timeout after 30 seconds');

        $this->assertEquals(SeverityEnum::High, $severity);
    }

    public function test_classifies_connection_refused_as_high(): void
    {
        $severity = $this->service->classifySeverity('Connection refused by server');

        $this->assertEquals(SeverityEnum::High, $severity);
    }

    public function test_classifies_connection_failed_as_high(): void
    {
        $severity = $this->service->classifySeverity('Database connection failed');

        $this->assertEquals(SeverityEnum::High, $severity);
    }

    public function test_classifies_deprecated_as_low(): void
    {
        $severity = $this->service->classifySeverity('Deprecated: Function is deprecated');

        $this->assertEquals(SeverityEnum::Low, $severity);
    }

    public function test_classifies_unauthorized_as_medium(): void
    {
        $severity = $this->service->classifySeverity('Unauthorized access attempt');

        $this->assertEquals(SeverityEnum::Medium, $severity);
    }

    public function test_classifies_forbidden_as_medium(): void
    {
        $severity = $this->service->classifySeverity('403 Forbidden');

        $this->assertEquals(SeverityEnum::Medium, $severity);
    }

    public function test_classifies_unknown_error_as_medium(): void
    {
        $severity = $this->service->classifySeverity('Some random error message');

        $this->assertEquals(SeverityEnum::Medium, $severity);
    }

    // Error Type Classification Tests

    public function test_classifies_sqlstate_as_database_error(): void
    {
        $errorType = $this->service->classifyErrorType('SQLSTATE[42S02]: Table not found');

        $this->assertEquals(ErrorTypeEnum::DatabaseError, $errorType);
    }

    public function test_classifies_database_keyword_as_database_error(): void
    {
        $errorType = $this->service->classifyErrorType('Database connection error');

        $this->assertEquals(ErrorTypeEnum::DatabaseError, $errorType);
    }

    public function test_classifies_query_keyword_as_database_error(): void
    {
        $errorType = $this->service->classifyErrorType('Query execution failed');

        $this->assertEquals(ErrorTypeEnum::DatabaseError, $errorType);
    }

    public function test_classifies_connection_as_network_error(): void
    {
        $errorType = $this->service->classifyErrorType('Connection refused');

        $this->assertEquals(ErrorTypeEnum::NetworkError, $errorType);
    }

    public function test_classifies_timeout_as_network_error(): void
    {
        $errorType = $this->service->classifyErrorType('Request timeout');

        $this->assertEquals(ErrorTypeEnum::NetworkError, $errorType);
    }

    public function test_classifies_network_keyword_as_network_error(): void
    {
        $errorType = $this->service->classifyErrorType('Network unreachable');

        $this->assertEquals(ErrorTypeEnum::NetworkError, $errorType);
    }

    public function test_classifies_unauthorized_as_auth_error(): void
    {
        $errorType = $this->service->classifyErrorType('Unauthorized access');

        $this->assertEquals(ErrorTypeEnum::AuthError, $errorType);
    }

    public function test_classifies_forbidden_as_auth_error(): void
    {
        $errorType = $this->service->classifyErrorType('403 Forbidden');

        $this->assertEquals(ErrorTypeEnum::AuthError, $errorType);
    }

    public function test_classifies_authentication_as_auth_error(): void
    {
        $errorType = $this->service->classifyErrorType('Authentication failed');

        $this->assertEquals(ErrorTypeEnum::AuthError, $errorType);
    }

    public function test_classifies_memory_as_performance_issue(): void
    {
        $errorType = $this->service->classifyErrorType('Memory limit exceeded');

        $this->assertEquals(ErrorTypeEnum::PerformanceIssue, $errorType);
    }

    public function test_classifies_slow_as_performance_issue(): void
    {
        $errorType = $this->service->classifyErrorType('Performance issue: Slow response time detected');

        $this->assertEquals(ErrorTypeEnum::PerformanceIssue, $errorType);
    }

    public function test_classifies_performance_keyword_as_performance_issue(): void
    {
        $errorType = $this->service->classifyErrorType('Performance degradation');

        $this->assertEquals(ErrorTypeEnum::PerformanceIssue, $errorType);
    }

    public function test_classifies_unknown_message_as_unknown(): void
    {
        $errorType = $this->service->classifyErrorType('Some random error');

        $this->assertEquals(ErrorTypeEnum::Unknown, $errorType);
    }

    // Generate Analysis Tests

    public function test_generates_analysis_for_database_error(): void
    {
        $analysis = $this->service->generateAnalysis('', ErrorTypeEnum::DatabaseError);

        $this->assertArrayHasKey('root_cause', $analysis);
        $this->assertArrayHasKey('suggested_fix', $analysis);
        $this->assertStringContainsString('Database', $analysis['root_cause']);
        $this->assertStringContainsString('database', $analysis['suggested_fix']);
    }

    public function test_generates_analysis_for_network_error(): void
    {
        $analysis = $this->service->generateAnalysis('', ErrorTypeEnum::NetworkError);

        $this->assertArrayHasKey('root_cause', $analysis);
        $this->assertArrayHasKey('suggested_fix', $analysis);
        $this->assertStringContainsString('Network', $analysis['root_cause']);
        $this->assertStringContainsString('network', $analysis['suggested_fix']);
    }

    public function test_generates_analysis_for_auth_error(): void
    {
        $analysis = $this->service->generateAnalysis('', ErrorTypeEnum::AuthError);

        $this->assertArrayHasKey('root_cause', $analysis);
        $this->assertArrayHasKey('suggested_fix', $analysis);
        $this->assertStringContainsString('Authentication', $analysis['root_cause']);
        $this->assertStringContainsString('credentials', $analysis['suggested_fix']);
    }

    public function test_generates_analysis_for_performance_issue(): void
    {
        $analysis = $this->service->generateAnalysis('', ErrorTypeEnum::PerformanceIssue);

        $this->assertArrayHasKey('root_cause', $analysis);
        $this->assertArrayHasKey('suggested_fix', $analysis);
        $this->assertStringContainsString('Performance', $analysis['root_cause']);
        $this->assertStringContainsString('resource', $analysis['suggested_fix']);
    }

    public function test_generates_analysis_for_unknown_error(): void
    {
        $analysis = $this->service->generateAnalysis('', ErrorTypeEnum::Unknown);

        $this->assertArrayHasKey('root_cause', $analysis);
        $this->assertArrayHasKey('suggested_fix', $analysis);
        $this->assertStringContainsString('Unable to determine', $analysis['root_cause']);
    }

    public function test_severity_classification_is_deterministic(): void
    {
        $message = 'SQLSTATE[23000]: Integrity constraint violation';

        $severity1 = $this->service->classifySeverity($message);
        $severity2 = $this->service->classifySeverity($message);

        $this->assertEquals($severity1, $severity2);
    }

    public function test_error_type_classification_is_deterministic(): void
    {
        $message = 'Database connection failed';

        $errorType1 = $this->service->classifyErrorType($message);
        $errorType2 = $this->service->classifyErrorType($message);

        $this->assertEquals($errorType1, $errorType2);
    }
}
