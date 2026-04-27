# Incident Intelligence System (IIS)

A Laravel 12 application for intelligent error tracking and analysis with AI-powered insights.

## Features

- **REST API** for incident ingestion
- **Deduplication** - Automatically groups duplicate incidents
- **Smart Classification** - Rule-based severity and error type detection
- **AI Analysis** (optional) - Root cause analysis via Ollama
- **Queue Processing** - Async analysis with retry logic
- **Web Dashboard** - View incidents, analysis, and recurring issues

## Quick Start

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iis
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Run Migrations & Seed Data

```bash
php artisan migrate
php artisan db:seed
```

### 4. Build Frontend Assets

```bash
npm run build
```

### 5. Start Queue Worker

```bash
php artisan queue:work
```

### 6. Access the Dashboard

Visit `http://iis.test` (or your configured domain) to view the dashboard.

## API Usage

### Submit an Incident

```bash
POST /api/incidents
Content-Type: application/json

{
  "service": "payments",
  "message": "SQLSTATE[23000]: Integrity constraint violation",
  "context": {
    "user_id": 123,
    "route": "/checkout"
  }
}
```

### List Incidents

```bash
GET /api/incidents?service=payments&severity=critical&status=open
```

### View Incident Details

```bash
GET /api/incidents/{id}
```

### Update Incident Status

```bash
PATCH /api/incidents/{id}
Content-Type: application/json

{
  "status": "resolved"
}
```

## Configuration

### AI Analysis (Optional)

To enable AI-powered analysis with Ollama:

1. Install Ollama: https://ollama.ai
2. Pull a model: `ollama pull llama3`
3. Update `.env`:

```env
INCIDENT_AI_ENABLED=true
INCIDENT_OLLAMA_URL=http://localhost:11434
INCIDENT_OLLAMA_MODEL=llama3
```

### Queue Configuration

By default, the system uses the `sync` driver. For production, use Redis:

```env
QUEUE_CONNECTION=redis
INCIDENT_QUEUE_CONNECTION=redis
```

## Dashboard Features

### Main Dashboard
- View all incidents with filtering by service, severity, and status
- Pagination (25 per page)
- Severity badges (Low, Medium, High, Critical)
- Occurrence counts

### Incident Details
- Full error message
- AI or rule-based analysis with confidence score
- Root cause and suggested fix
- Occurrences timeline with context
- Status update form

### Recurring Issues
- Incidents sorted by occurrence count
- Filter by service and severity
- First and last occurrence timestamps

## Architecture

### Services

- **DeduplicationService** - SHA-256 hash generation and duplicate detection
- **NormalizationService** - Strips variable data from error messages
- **ClassificationService** - Rule-based severity and error type classification
- **OllamaService** - AI integration for enhanced analysis
- **AnalysisService** - Orchestrates analysis flow with AI fallback

### Queue Jobs

- **IncidentCreatedJob** - Dispatched on incident creation
- **AnalyzeIncidentJob** - Performs analysis (3 retries, 60s backoff, 35s timeout)

### Models

- **Incident** - Main incident record with UUID primary key
- **IncidentAnalysis** - Analysis results (root cause, fix, confidence)
- **IncidentOccurrence** - Duplicate incident tracking with context

## Development

### Run Tests (Optional)

```bash
php artisan test
```

### Code Formatting

```bash
vendor/bin/pint
```

### Watch Assets

```bash
npm run dev
```

## Tech Stack

- Laravel 12
- PHP 8.4
- MySQL/PostgreSQL
- Redis (for queues)
- Tailwind CSS 3
- Alpine.js 3
- Ollama (optional AI)

## License

MIT
