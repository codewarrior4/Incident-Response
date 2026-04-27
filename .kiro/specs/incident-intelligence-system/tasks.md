# Implementation Plan: Incident Intelligence System

## Overview

This implementation plan creates a standalone Laravel 12 application for intelligent error tracking and analysis. The system provides a REST API for incident ingestion, asynchronous queue-based processing with rule-based and optional AI-powered analysis, and a web dashboard for incident management. Implementation follows Laravel 12 best practices with proper separation of concerns, comprehensive testing, and scalable architecture.

## Tasks

- [x] 1. Set up database schema and migrations
  - [x] 1.1 Create incidents table migration
    - Create migration with UUID primary key, title, message (text), service, severity enum, hash (unique), status enum, timestamps
    - Add indexes on service, severity, status, created_at, and unique index on hash
    - _Requirements: 10.1, 10.4, 10.5, 10.6, 10.7, 10.8_
  
  - [x] 1.2 Create incident_analyses table migration
    - Create migration with UUID primary key, incident_id foreign key (cascade delete), root_cause (text), suggested_fix (text), confidence_score (integer), ai_generated (boolean), timestamps
    - Add index on incident_id
    - _Requirements: 10.2, 10.9_
  
  - [x] 1.3 Create incident_occurrences table migration
    - Create migration with UUID primary key, incident_id foreign key (cascade delete), context (JSON), timestamps
    - Add indexes on incident_id and created_at
    - _Requirements: 10.3, 10.10_

- [x] 2. Create enums and models
  - [x] 2.1 Create SeverityEnum, StatusEnum, and ErrorTypeEnum
    - Create SeverityEnum with Low, Medium, High, Critical cases
    - Create StatusEnum with Open, Investigating, Resolved cases
    - Create ErrorTypeEnum with DatabaseError, NetworkError, AuthError, PerformanceIssue, Unknown cases
    - _Requirements: 3.9, 7.2, 5.2_
  
  - [x] 2.2 Create Incident model with relationships and casts
    - Use HasUuids trait, define fillable fields, cast severity and status to enums
    - Define hasOne analysis relationship and hasMany occurrences relationship
    - Add getOccurrencesCountAttribute accessor (count + 1 for original)
    - _Requirements: 1.8, 1.9, 3.9, 7.1, 7.2_
  
  - [x] 2.3 Create IncidentAnalysis model
    - Use HasUuids trait, define fillable fields, cast confidence_score to integer and ai_generated to boolean
    - Define belongsTo incident relationship
    - _Requirements: 5.6, 5.7_
  
  - [x] 2.4 Create IncidentOccurrence model
    - Use HasUuids trait, define fillable fields, cast context to array
    - Define belongsTo incident relationship
    - _Requirements: 2.4_

- [x] 3. Create service classes for core logic
  - [x] 3.1 Create DeduplicationService
    - Implement generateHash method using SHA-256 of service + message
    - Implement findExistingIncident method to query by hash
    - Implement recordOccurrence method to create occurrence record
    - _Requirements: 2.1, 2.2, 2.3, 2.4_
  
  - [x] 3.2 Create NormalizationService
    - Implement normalize method to strip timestamps, file paths, line numbers, UUIDs, IDs
    - Normalize whitespace and return trimmed result
    - _Requirements: 5.1_
  
  - [x] 3.3 Create ClassificationService
    - Implement classifySeverity method with pattern matching for critical, high, medium, low
    - Implement classifyErrorType method to detect database, network, auth, performance errors
    - Implement generateAnalysis method with match expression for error types
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 5.2, 5.3, 5.4_
  
  - [x] 3.4 Create OllamaService
    - Implement analyze method to send HTTP POST to Ollama API with timeout
    - Implement buildPrompt method to format error analysis prompt
    - Implement parseResponse method to extract root_cause and suggested_fix from AI response
    - _Requirements: 6.1, 6.2, 6.5, 6.6_
  
  - [x] 3.5 Create AnalysisService
    - Implement analyze method orchestrating normalization, classification, and AI/rule-based analysis
    - Implement generateAiAnalysis method with Ollama integration and confidence 80-95
    - Implement generateRuleBasedAnalysis method with confidence 60-80
    - Add try-catch for AI fallback to rule-based analysis
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.8, 5.9, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 4. Create API form requests and resources
  - [x] 4.1 Create StoreIncidentRequest
    - Validate service (required, string, max:255), message (required, string), context (nullable, array)
    - _Requirements: 1.2, 1.4, 1.5, 1.6, 1.7_
  
  - [x] 4.2 Create UpdateIncidentRequest
    - Validate status (required, in:open,investigating,resolved)
    - _Requirements: 7.3_
  
  - [x] 4.3 Create IncidentResource
    - Return id, title, message, service, severity, status, hash, occurrences_count, timestamps
    - Include analysis and occurrences relationships when loaded
    - _Requirements: 11.1, 11.2, 11.3_
  
  - [x] 4.4 Create IncidentAnalysisResource
    - Return id, root_cause, suggested_fix, confidence_score, ai_generated, created_at
    - _Requirements: 11.2_
  
  - [x] 4.5 Create IncidentOccurrenceResource
    - Return id, context, created_at
    - _Requirements: 11.3_

- [x] 5. Create API controller and routes
  - [x] 5.1 Create IncidentController with store, index, show, update methods
    - Implement store method: validate, check for duplicate via DeduplicationService, create or record occurrence, dispatch IncidentCreatedJob, return 201 or 200
    - Implement index method: query with filters (service, severity, status), eager load relationships, paginate 25, return IncidentResource collection
    - Implement show method: load relationships, return IncidentResource
    - Implement update method: validate status, update incident, return IncidentResource
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.5, 4.1, 7.3, 7.4, 11.1, 11.2, 11.3, 11.4_
  
  - [x] 5.2 Add API routes in routes/api.php
    - POST /api/incidents, GET /api/incidents, GET /api/incidents/{incident}, PATCH /api/incidents/{incident}
    - _Requirements: 1.1_

- [x] 6. Create queue jobs for asynchronous processing
  - [x] 6.1 Create IncidentCreatedJob
    - Set tries=3, backoff=60, dispatch AnalyzeIncidentJob in handle method
    - Implement failed method to log error with incident context
    - _Requirements: 4.1, 4.2, 4.4, 4.5, 4.6, 13.6_
  
  - [x] 6.2 Create AnalyzeIncidentJob
    - Set tries=3, backoff=60, timeout=35, inject AnalysisService in handle method
    - Skip if analysis already exists, call analysisService->analyze
    - Implement failed method to log error and set incident status to open
    - _Requirements: 4.2, 4.4, 4.5, 4.7, 5.5, 13.3, 13.5, 13.6_

- [x] 7. Create configuration file
  - [x] 7.1 Create config/incident-intelligence.php
    - Define ai_enabled, ollama_url, ollama_model, ollama_timeout, queue_connection, analysis_retry_attempts
    - Use env() with defaults: ai_enabled=false, ollama_url=http://localhost:11434, ollama_model=llama3, ollama_timeout=25, queue_connection=redis, analysis_retry_attempts=3
    - _Requirements: 6.7, 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7_
  
  - [x] 7.2 Update .env.example with incident intelligence variables
    - Add INCIDENT_AI_ENABLED, INCIDENT_OLLAMA_URL, INCIDENT_OLLAMA_MODEL, INCIDENT_OLLAMA_TIMEOUT, INCIDENT_QUEUE_CONNECTION, INCIDENT_ANALYSIS_RETRY_ATTEMPTS
    - _Requirements: 12.2, 12.3, 12.4, 12.5, 12.6, 12.7_

- [x] 8. Create dashboard controllers and views
  - [x] 8.1 Create DashboardController with index, show, recurring methods
    - Implement index method: query with filters, eager load, withCount occurrences, paginate 25, return view with incidents and services
    - Implement show method: load analysis and occurrences relationships, return detail view
    - Implement recurring method: query with occurrences_count >= 1, filter by service/severity, order by occurrences_count desc, paginate 25
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_
  
  - [x] 8.2 Create web routes in routes/web.php
    - GET / (dashboard.index), GET /incidents/{incident} (dashboard.show), GET /recurring (dashboard.recurring)
    - _Requirements: 8.1, 9.1_
  
  - [x] 8.3 Create layouts/app.blade.php layout
    - Include Tailwind CSS, Alpine.js 3, navigation menu, flash message display
    - _Requirements: 8.1_
  
  - [x] 8.4 Create dashboard/index.blade.php view
    - Display paginated incident list with title, service, severity badge, status, created_at
    - Add filter controls for service, severity, status using Alpine.js
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
  
  - [x] 8.5 Create dashboard/show.blade.php view
    - Display full incident details, analysis section with confidence score, occurrences timeline
    - Add status update form using Alpine.js
    - _Requirements: 8.6, 8.7, 8.8, 8.9, 8.10_
  
  - [x] 8.6 Create dashboard/recurring.blade.php view
    - Display incidents sorted by occurrence count with first/last occurrence timestamps
    - Add filter controls for service and severity
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7_

- [x] 9. Create factories and seeders
  - [x] 9.1 Create IncidentFactory with states
    - Define definition with fake data for all fields, generate realistic hash
    - Add critical() and resolved() states
    - _Requirements: 14.9_
  
  - [x] 9.2 Create IncidentAnalysisFactory with states
    - Define definition with incident_id, fake root_cause/suggested_fix, random confidence_score, random ai_generated
    - Add aiGenerated() state (confidence 80-95) and ruleBased() state (confidence 60-80)
    - _Requirements: 14.9_
  
  - [x] 9.3 Create IncidentOccurrenceFactory
    - Define definition with incident_id and realistic context JSON (user_id, ip_address, user_agent)
    - _Requirements: 14.9_
  
  - [x] 9.4 Create DatabaseSeeder to populate sample data
    - Create 50 incidents with varied services, severities, statuses
    - Create analyses for all incidents (mix of AI and rule-based)
    - Create 2-5 occurrences for 20 incidents to simulate recurring issues
    - _Requirements: 14.9_

- [x] 10. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Write unit tests for service classes
  - [x]* 11.1 Write unit tests for DeduplicationService
    - Test generateHash produces SHA-256 hash
    - Test findExistingIncident returns null for new hash and incident for existing hash
    - Test recordOccurrence creates occurrence record with context
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 14.7_
  
  - [x]* 11.2 Write unit tests for NormalizationService
    - Test removes timestamps, file paths, line numbers, UUIDs, IDs
    - Test normalizes whitespace
    - **Property 12: Normalization is idempotent**
    - **Validates: Requirements 5.1**
    - _Requirements: 5.1, 14.4_
  
  - [x]* 11.3 Write unit tests for ClassificationService
    - Test classifySeverity for all patterns (SQLSTATE→critical, timeout→high, deprecated→low, unauthorized→medium, default→medium)
    - Test classifyErrorType for database, network, auth, performance, unknown
    - Test generateAnalysis returns correct root_cause and suggested_fix for each error type
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 5.2, 14.3, 14.4_
  
  - [ ]* 11.4 Write unit tests for AnalysisService
    - Test generates rule-based analysis when AI disabled (ai_generated=false, confidence 60-80)
    - Test generates AI analysis when enabled (ai_generated=true, confidence 80-95) with mocked Ollama
    - Test falls back to rule-based when Ollama unavailable or times out
    - _Requirements: 5.8, 5.9, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 14.8_
  
  - [ ]* 11.5 Write unit tests for OllamaService with mocked HTTP client
    - Test analyze sends correct payload to Ollama API
    - Test parseResponse extracts root_cause and suggested_fix
    - Test throws exception on timeout or connection failure
    - _Requirements: 6.1, 6.2, 6.5, 6.6, 14.8_

- [ ] 12. Write unit tests for models
  - [ ]* 12.1 Write unit tests for Incident model
    - Test has UUID primary key, timestamps, severity/status enum casts
    - Test analysis and occurrences relationships
    - Test occurrences_count attribute includes original (+1)
    - _Requirements: 1.8, 1.9, 3.9, 7.2_
  
  - [ ]* 12.2 Write unit tests for IncidentAnalysis model
    - Test has UUID primary key, belongs to incident, casts confidence_score to integer and ai_generated to boolean
    - _Requirements: 5.6, 5.7_
  
  - [ ]* 12.3 Write unit tests for IncidentOccurrence model
    - Test has UUID primary key, belongs to incident, casts context to array
    - _Requirements: 2.4_

- [x] 13. Write feature tests for API endpoints
  - [x]* 13.1 Write feature tests for incident creation (IncidentControllerTest)
    - Test creates incident with valid data returns 201 with incident resource
    - Test missing service returns 422 with validation errors
    - Test missing message returns 422 with validation errors
    - Test malformed JSON returns 400
    - Test created incident has UUID, timestamps, default status=open
    - **Property 1: Valid incident submission returns 201**
    - **Validates: Requirements 1.3, 11.1**
    - **Property 2: Invalid incident submission returns 422**
    - **Validates: Requirements 1.4, 11.5**
    - **Property 3: Created incidents have UUID and timestamps**
    - **Validates: Requirements 1.8, 1.9**
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.8, 1.9, 7.1, 11.1, 11.5, 13.1, 14.1_
  
  - [x]* 13.2 Write feature tests for incident listing and filtering
    - Test lists incidents with pagination (25 per page)
    - Test filters by service, severity, status
    - Test response includes data, links, meta objects
    - **Property 20: Paginated responses have correct structure**
    - **Validates: Requirements 11.4**
    - _Requirements: 11.4, 14.1_
  
  - [x]* 13.3 Write feature tests for incident show endpoint
    - Test shows single incident with analysis and occurrences relationships
    - Test response includes root_cause, suggested_fix, confidence_score, ai_generated, occurrences_count
    - **Property 19: API responses include required relationships**
    - **Validates: Requirements 11.2, 11.3**
    - _Requirements: 11.2, 11.3, 14.1_
  
  - [x]* 13.4 Write feature tests for incident status update
    - Test updates status with valid enum value
    - Test updates updated_at timestamp
    - Test rejects invalid status with 422
    - **Property 16: Status updates only accept valid enum values**
    - **Validates: Requirements 7.3**
    - **Property 17: Status updates modify updated_at timestamp**
    - **Validates: Requirements 7.4**
    - _Requirements: 7.3, 7.4, 14.1_

- [ ] 14. Write feature tests for deduplication
  - [x]* 14.1 Write deduplication feature tests
    - Test creates new incident for unique hash
    - Test creates occurrence for duplicate hash (not new incident)
    - Test returns 200 for duplicate incident
    - Test occurrence contains context and timestamp
    - Test unique constraint on hash column
    - Test handles concurrent duplicate submissions
    - **Property 4: Hash generation is deterministic**
    - **Validates: Requirements 2.1**
    - **Property 5: Duplicate submission creates occurrence, not new incident**
    - **Validates: Requirements 2.2, 2.3, 2.5**
    - **Property 6: Occurrence records contain required fields**
    - **Validates: Requirements 2.4**
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 14.2, 14.7_

- [ ] 15. Write feature tests for queue jobs
  - [ ]* 15.1 Write queue job feature tests
    - Test IncidentCreatedJob dispatches AnalyzeIncidentJob
    - Test AnalyzeIncidentJob creates analysis record
    - Test AnalyzeIncidentJob skips already analyzed incidents
    - Test failed job retries 3 times with exponential backoff
    - Test failed job logs error after all retries
    - Test failed job keeps incident status as open
    - **Property 10: Incident creation dispatches queue job**
    - **Validates: Requirements 4.1**
    - **Property 11: IncidentCreatedJob dispatches AnalyzeIncidentJob**
    - **Validates: Requirements 4.2**
    - _Requirements: 4.1, 4.2, 4.4, 4.5, 13.3, 13.5, 13.6, 14.5_

- [ ] 16. Write feature tests for dashboard views
  - [ ]* 16.1 Write dashboard feature tests
    - Test dashboard index displays incidents with pagination
    - Test dashboard index filters by service, severity, status
    - Test dashboard show displays incident details, analysis, occurrences timeline
    - Test recurring issues view shows incidents with 2+ occurrences ordered by count
    - Test recurring issues view filters by service and severity
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 8.10, 9.1, 9.2, 9.3, 9.4, 9.5, 9.6, 9.7, 14.6_

- [ ] 17. Write property-based tests for correctness properties
  - [ ]* 17.1 Write property tests for severity and error type classification
    - **Property 7: All incidents have valid severity classification**
    - **Validates: Requirements 3.1, 3.9**
    - **Property 8: Severity classification is deterministic**
    - **Validates: Requirements 3.2-3.8**
    - **Property 9: All incidents have valid error type classification**
    - **Validates: Requirements 5.2**
    - Run 100 iterations with randomized messages, verify severity/error type are valid enums and deterministic
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9, 5.2, 14.3, 14.4_
  
  - [ ]* 17.2 Write property tests for analysis records
    - **Property 13: All analyses have required fields**
    - **Validates: Requirements 5.3, 5.4, 5.5, 5.6**
    - **Property 14: Confidence score is within valid range**
    - **Validates: Requirements 5.7**
    - Run 100 iterations creating analyses, verify all required fields present and confidence_score 0-100
    - _Requirements: 5.3, 5.4, 5.5, 5.6, 5.7_
  
  - [ ]* 17.3 Write property tests for incident status management
    - **Property 15: New incidents default to open status**
    - **Validates: Requirements 7.1**
    - **Property 18: All incidents have valid status**
    - **Validates: Requirements 7.2**
    - Run 100 iterations creating incidents, verify status defaults to open and is always valid enum
    - _Requirements: 7.1, 7.2_
  
  - [ ]* 17.4 Write property tests for error handling
    - **Property 21: Error responses have consistent format**
    - **Validates: Requirements 11.5**
    - **Property 22: Analysis errors are logged with context**
    - **Validates: Requirements 13.6**
    - **Property 23: Error responses do not expose sensitive information**
    - **Validates: Requirements 13.7**
    - Run 100 iterations with various error scenarios, verify response format and no sensitive data exposure
    - _Requirements: 11.5, 13.6, 13.7_

- [ ] 18. Final checkpoint - Run full test suite
  - Run full test suite with coverage: php artisan test --coverage --min=80
  - Verify all 23 correctness properties are tested
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties across randomized inputs
- Unit tests validate specific examples and edge cases
- This is a standalone Laravel 12 application (not part of onely)
- Follow Laravel 12 conventions: middleware in bootstrap/app.php, casts() method on models
- Use Laravel Horizon for queue management with Redis
- All models use UUID primary keys with HasUuids trait
- Minimum 80% code coverage required for IIS components
