# Requirements Document

## Introduction

The Incident Intelligence System (IIS) is a backend system that ingests errors and logs from applications, performs intelligent analysis, and provides actionable insights. The system uses a rule-based analysis engine with optional AI enhancement to classify incidents, detect duplicates, identify root causes, and suggest fixes. The architecture follows Laravel best practices with queue-based processing, proper database design, and a dashboard for incident management.

## Glossary

- **IIS**: Incident Intelligence System - the complete backend system
- **Incident**: An error or log entry submitted to the system
- **Incident_Store**: Database storage for incidents and related data
- **Ingestion_API**: REST API endpoint that accepts incident submissions
- **Normalization_Layer**: Component that strips noise and extracts error patterns
- **Processing_Layer**: Queue-based job system for async incident processing
- **Analysis_Engine**: Rule-based classifier with optional AI enhancement
- **Deduplication_System**: Hash-based system to detect repeated incidents
- **Dashboard**: Web interface for viewing and managing incidents
- **Service**: The application component where the incident occurred (auth, payments, api, etc.)
- **Severity**: Classification level (low, medium, high, critical)
- **Status**: Incident lifecycle state (open, investigating, resolved)
- **Hash**: SHA-256 hash of service + message for deduplication
- **Occurrence**: A single instance of a recurring incident
- **Analysis**: Root cause analysis and suggested fix for an incident
- **Confidence_Score**: Numeric value (0-100) indicating analysis reliability
- **Ollama**: Local AI service for enhanced analysis (LLaMA 3 or Mistral)

## Requirements

### Requirement 1: Incident Ingestion

**User Story:** As a developer, I want to submit errors and logs to the IIS, so that they can be analyzed and tracked.

#### Acceptance Criteria

1. THE Ingestion_API SHALL accept POST requests at /api/incidents endpoint
2. WHEN an incident is submitted, THE Ingestion_API SHALL validate that service, message, and context fields are present
3. WHEN an incident is submitted with valid data, THE Ingestion_API SHALL return HTTP 201 with the created incident resource
4. WHEN an incident is submitted with invalid data, THE Ingestion_API SHALL return HTTP 422 with validation errors
5. THE Ingestion_API SHALL accept service as a string identifying the application component
6. THE Ingestion_API SHALL accept message as a string containing the error message
7. THE Ingestion_API SHALL accept context as a JSON object containing additional metadata
8. WHEN an incident is created, THE Incident_Store SHALL store the incident with a generated UUID
9. WHEN an incident is created, THE Incident_Store SHALL store timestamps for created_at and updated_at

### Requirement 2: Incident Deduplication

**User Story:** As a system administrator, I want duplicate incidents to be detected automatically, so that I can focus on unique issues.

#### Acceptance Criteria

1. WHEN an incident is submitted, THE Deduplication_System SHALL generate a hash using SHA-256 of service concatenated with message
2. WHEN an incident hash matches an existing incident, THE Deduplication_System SHALL increment the occurrence count instead of creating a new incident
3. WHEN a duplicate incident is detected, THE Incident_Store SHALL create a new record in incident_occurrences table
4. THE incident_occurrences record SHALL store the incident_id, context JSON, and timestamp
5. WHEN a duplicate incident is detected, THE Ingestion_API SHALL return HTTP 200 with the existing incident resource
6. THE Incident_Store SHALL maintain a unique index on the hash column in the incidents table

### Requirement 3: Severity Classification

**User Story:** As a developer, I want incidents to be automatically classified by severity, so that I can prioritize critical issues.

#### Acceptance Criteria

1. WHEN an incident is created, THE Analysis_Engine SHALL classify the severity as low, medium, high, or critical
2. WHEN the incident message contains "timeout", THE Analysis_Engine SHALL classify severity as high
3. WHEN the incident message contains "SQLSTATE", THE Analysis_Engine SHALL classify severity as critical
4. WHEN the incident message contains "deprecated", THE Analysis_Engine SHALL classify severity as low
5. WHEN the incident message contains "connection refused" or "connection failed", THE Analysis_Engine SHALL classify severity as high
6. WHEN the incident message contains "out of memory" or "memory exhausted", THE Analysis_Engine SHALL classify severity as critical
7. WHEN the incident message contains "unauthorized" or "forbidden", THE Analysis_Engine SHALL classify severity as medium
8. WHEN no severity rules match, THE Analysis_Engine SHALL classify severity as medium
9. THE Incident_Store SHALL store severity as an enum column with values: low, medium, high, critical

### Requirement 4: Asynchronous Processing

**User Story:** As a system architect, I want incident processing to be asynchronous, so that the ingestion API remains fast and responsive.

#### Acceptance Criteria

1. WHEN an incident is created, THE Ingestion_API SHALL dispatch an IncidentCreatedJob to the queue
2. WHEN IncidentCreatedJob is processed, THE Processing_Layer SHALL dispatch an AnalyzeIncidentJob to the queue
3. THE Processing_Layer SHALL use Laravel Horizon for queue management
4. WHEN a queue job fails, THE Processing_Layer SHALL retry up to 3 times with exponential backoff
5. WHEN a queue job fails after all retries, THE Processing_Layer SHALL log the failure and mark the incident status as open
6. THE IncidentCreatedJob SHALL complete within 5 seconds
7. THE AnalyzeIncidentJob SHALL complete within 30 seconds for rule-based analysis

### Requirement 5: Incident Analysis

**User Story:** As a developer, I want incidents to be analyzed for root cause and suggested fixes, so that I can resolve issues faster.

#### Acceptance Criteria

1. WHEN an incident is analyzed, THE Analysis_Engine SHALL normalize the incident by stripping noise and extracting error patterns
2. WHEN an incident is normalized, THE Analysis_Engine SHALL classify the error type as database_error, network_error, auth_error, or performance_issue
3. WHEN an incident is classified, THE Analysis_Engine SHALL generate a root cause explanation
4. WHEN an incident is classified, THE Analysis_Engine SHALL generate suggested fixes
5. WHEN analysis is complete, THE Incident_Store SHALL create a record in incident_analyses table
6. THE incident_analyses record SHALL store incident_id, root_cause, suggested_fix, confidence_score, and ai_generated flag
7. THE confidence_score SHALL be an integer between 0 and 100
8. WHEN rule-based analysis is used, THE Analysis_Engine SHALL set ai_generated to false
9. WHEN rule-based analysis is used, THE Analysis_Engine SHALL set confidence_score between 60 and 80

### Requirement 6: AI Enhancement

**User Story:** As a system administrator, I want to optionally enhance incident analysis with AI, so that I can get more accurate root cause analysis.

#### Acceptance Criteria

1. WHERE AI enhancement is enabled, THE Analysis_Engine SHALL send the normalized incident to Ollama
2. WHERE AI enhancement is enabled, WHEN Ollama returns a response, THE Analysis_Engine SHALL use the AI-generated root cause and suggested fix
3. WHERE AI enhancement is enabled, WHEN Ollama returns a response, THE Analysis_Engine SHALL set ai_generated to true
4. WHERE AI enhancement is enabled, WHEN Ollama returns a response, THE Analysis_Engine SHALL set confidence_score between 80 and 95
5. WHERE AI enhancement is enabled, IF Ollama is unavailable, THEN THE Analysis_Engine SHALL fall back to rule-based analysis
6. WHERE AI enhancement is enabled, IF Ollama request times out after 25 seconds, THEN THE Analysis_Engine SHALL fall back to rule-based analysis
7. THE Analysis_Engine SHALL read AI configuration from config/incident-intelligence.php
8. THE Analysis_Engine SHALL support LLaMA 3 and Mistral models via Ollama

### Requirement 7: Incident Status Management

**User Story:** As a developer, I want to track incident lifecycle status, so that I can manage incident resolution workflow.

#### Acceptance Criteria

1. WHEN an incident is created, THE Incident_Store SHALL set status to open
2. THE Incident_Store SHALL store status as an enum column with values: open, investigating, resolved
3. WHEN an incident status is updated via API, THE Ingestion_API SHALL validate the new status is a valid enum value
4. WHEN an incident is marked as resolved, THE Incident_Store SHALL update the updated_at timestamp
5. THE Dashboard SHALL allow users to filter incidents by status

### Requirement 8: Incident Dashboard

**User Story:** As a developer, I want to view incidents in a dashboard, so that I can monitor and manage system errors.

#### Acceptance Criteria

1. THE Dashboard SHALL display a paginated list of incidents with 25 items per page
2. THE Dashboard SHALL display incident title, service, severity badge, status, and created_at timestamp
3. THE Dashboard SHALL allow filtering incidents by service
4. THE Dashboard SHALL allow filtering incidents by severity
5. THE Dashboard SHALL allow filtering incidents by status
6. WHEN a user clicks an incident, THE Dashboard SHALL display the incident detail page
7. THE incident detail page SHALL display the full incident message, context, and all occurrences
8. THE incident detail page SHALL display the root cause analysis and suggested fix
9. THE incident detail page SHALL display an occurrences timeline showing when duplicates occurred
10. THE incident detail page SHALL display the confidence score and whether AI was used

### Requirement 9: Recurring Issues Detection

**User Story:** As a system administrator, I want to identify recurring issues, so that I can prioritize fixing systemic problems.

#### Acceptance Criteria

1. THE Dashboard SHALL provide a recurring issues view
2. THE recurring issues view SHALL display incidents ordered by occurrence count descending
3. THE recurring issues view SHALL display the occurrence count for each incident
4. THE recurring issues view SHALL display the first occurrence timestamp and last occurrence timestamp
5. THE recurring issues view SHALL only display incidents with 2 or more occurrences
6. THE recurring issues view SHALL allow filtering by service
7. THE recurring issues view SHALL allow filtering by severity

### Requirement 10: Database Schema

**User Story:** As a database administrator, I want a well-designed schema, so that the system can scale and perform efficiently.

#### Acceptance Criteria

1. THE Incident_Store SHALL have an incidents table with columns: id (UUID), title, message (text), service, severity (enum), hash, status (enum), created_at, updated_at
2. THE Incident_Store SHALL have an incident_analyses table with columns: id (UUID), incident_id (UUID foreign key), root_cause (text), suggested_fix (text), confidence_score (integer), ai_generated (boolean), created_at, updated_at
3. THE Incident_Store SHALL have an incident_occurrences table with columns: id (UUID), incident_id (UUID foreign key), context (JSON), created_at, updated_at
4. THE incidents table SHALL have a unique index on hash column
5. THE incidents table SHALL have an index on service column
6. THE incidents table SHALL have an index on severity column
7. THE incidents table SHALL have an index on status column
8. THE incidents table SHALL have an index on created_at column
9. THE incident_analyses table SHALL have a foreign key constraint on incident_id with cascade delete
10. THE incident_occurrences table SHALL have a foreign key constraint on incident_id with cascade delete

### Requirement 11: API Response Format

**User Story:** As an API consumer, I want consistent response formats, so that I can reliably parse API responses.

#### Acceptance Criteria

1. WHEN an incident is created, THE Ingestion_API SHALL return JSON with incident resource including id, title, message, service, severity, status, hash, created_at, updated_at
2. WHEN an incident is retrieved, THE Ingestion_API SHALL include the analysis relationship with root_cause, suggested_fix, confidence_score, ai_generated
3. WHEN an incident is retrieved, THE Ingestion_API SHALL include the occurrences_count attribute
4. WHEN an incident list is requested, THE Ingestion_API SHALL return paginated JSON with data, links, and meta objects
5. WHEN an API error occurs, THE Ingestion_API SHALL return JSON with message and errors fields
6. THE Ingestion_API SHALL use Laravel API Resources for response formatting

### Requirement 12: Configuration Management

**User Story:** As a system administrator, I want to configure IIS behavior via environment variables, so that I can customize the system without code changes.

#### Acceptance Criteria

1. THE IIS SHALL read configuration from config/incident-intelligence.php
2. THE configuration file SHALL define ai_enabled boolean from INCIDENT_AI_ENABLED environment variable
3. THE configuration file SHALL define ollama_url from INCIDENT_OLLAMA_URL environment variable with default http://localhost:11434
4. THE configuration file SHALL define ollama_model from INCIDENT_OLLAMA_MODEL environment variable with default llama3
5. THE configuration file SHALL define ollama_timeout from INCIDENT_OLLAMA_TIMEOUT environment variable with default 25 seconds
6. THE configuration file SHALL define analysis_retry_attempts from INCIDENT_ANALYSIS_RETRY_ATTEMPTS environment variable with default 3
7. THE configuration file SHALL define queue_connection from INCIDENT_QUEUE_CONNECTION environment variable with default redis

### Requirement 13: Error Handling

**User Story:** As a developer, I want robust error handling, so that the system remains stable when unexpected errors occur.

#### Acceptance Criteria

1. WHEN the Ingestion_API receives malformed JSON, THE Ingestion_API SHALL return HTTP 400 with error message
2. WHEN the Incident_Store encounters a database error, THE Ingestion_API SHALL return HTTP 500 with generic error message
3. WHEN the Analysis_Engine encounters an error, THE Processing_Layer SHALL log the error and retry the job
4. WHEN Ollama is unavailable, THE Analysis_Engine SHALL log a warning and fall back to rule-based analysis
5. WHEN an incident cannot be analyzed after all retries, THE Incident_Store SHALL keep the incident with status open and no analysis
6. THE IIS SHALL log all errors to Laravel log files with context
7. THE IIS SHALL not expose sensitive information in error responses

### Requirement 14: Testing Requirements

**User Story:** As a quality assurance engineer, I want comprehensive tests, so that I can ensure system reliability.

#### Acceptance Criteria

1. THE IIS SHALL have feature tests for incident ingestion API covering happy path and validation errors
2. THE IIS SHALL have feature tests for deduplication logic
3. THE IIS SHALL have unit tests for severity classification rules
4. THE IIS SHALL have unit tests for error type classification
5. THE IIS SHALL have feature tests for queue job processing
6. THE IIS SHALL have feature tests for dashboard views
7. THE IIS SHALL have unit tests for hash generation
8. THE IIS SHALL have integration tests for AI enhancement with mocked Ollama responses
9. ALL tests SHALL use factories for model creation
10. ALL tests SHALL achieve minimum 80% code coverage for IIS components
