<?php

namespace App\Services;

use App\Enums\ErrorTypeEnum;
use App\Enums\SeverityEnum;

class ClassificationService
{
    public function classifySeverity(string $normalizedMessage): SeverityEnum
    {
        $message = strtolower($normalizedMessage);

        // Critical patterns
        if (str_contains($message, 'sqlstate') ||
            str_contains($message, 'out of memory') ||
            str_contains($message, 'memory exhausted')) {
            return SeverityEnum::Critical;
        }

        // High patterns
        if (str_contains($message, 'timeout') ||
            str_contains($message, 'connection refused') ||
            str_contains($message, 'connection failed')) {
            return SeverityEnum::High;
        }

        // Low patterns
        if (str_contains($message, 'deprecated')) {
            return SeverityEnum::Low;
        }

        // Medium patterns (including auth)
        if (str_contains($message, 'unauthorized') ||
            str_contains($message, 'forbidden')) {
            return SeverityEnum::Medium;
        }

        // Default
        return SeverityEnum::Medium;
    }

    public function classifyErrorType(string $normalizedMessage): ErrorTypeEnum
    {
        $message = strtolower($normalizedMessage);

        if (str_contains($message, 'sqlstate') ||
            str_contains($message, 'database') ||
            str_contains($message, 'query')) {
            return ErrorTypeEnum::DatabaseError;
        }

        if (str_contains($message, 'connection') ||
            str_contains($message, 'timeout') ||
            str_contains($message, 'network')) {
            return ErrorTypeEnum::NetworkError;
        }

        if (str_contains($message, 'unauthorized') ||
            str_contains($message, 'forbidden') ||
            str_contains($message, 'authentication')) {
            return ErrorTypeEnum::AuthError;
        }

        if (str_contains($message, 'memory') ||
            str_contains($message, 'slow') ||
            str_contains($message, 'performance')) {
            return ErrorTypeEnum::PerformanceIssue;
        }

        return ErrorTypeEnum::Unknown;
    }

    public function generateAnalysis(string $normalized, ErrorTypeEnum $errorType): array
    {
        return match ($errorType) {
            ErrorTypeEnum::DatabaseError => [
                'root_cause' => 'Database query error or connection issue detected.',
                'suggested_fix' => 'Check database connection settings, verify query syntax, and ensure database server is accessible.',
            ],
            ErrorTypeEnum::NetworkError => [
                'root_cause' => 'Network connectivity issue or timeout detected.',
                'suggested_fix' => 'Verify network connectivity, check firewall rules, and increase timeout settings if necessary.',
            ],
            ErrorTypeEnum::AuthError => [
                'root_cause' => 'Authentication or authorization failure detected.',
                'suggested_fix' => 'Verify user credentials, check permission settings, and ensure authentication tokens are valid.',
            ],
            ErrorTypeEnum::PerformanceIssue => [
                'root_cause' => 'Performance degradation or resource exhaustion detected.',
                'suggested_fix' => 'Optimize resource usage, increase memory limits, and review performance bottlenecks.',
            ],
            ErrorTypeEnum::Unknown => [
                'root_cause' => 'Unable to determine specific error type from the incident message.',
                'suggested_fix' => 'Review the full error message and context for more details.',
            ],
        };
    }
}
