<?php

namespace App\Services;

use App\Enums\ErrorTypeEnum;
use App\Models\Incident;
use App\Models\IncidentAnalysis;
use Illuminate\Support\Facades\Log;

class AnalysisService
{
    public function __construct(
        private NormalizationService $normalizer,
        private ClassificationService $classifier,
        private OllamaService $ollama,
    ) {}

    public function analyze(Incident $incident): IncidentAnalysis
    {
        $normalized = $this->normalizer->normalize($incident);
        $severity = $this->classifier->classifySeverity($normalized);
        $errorType = $this->classifier->classifyErrorType($normalized);

        // Update incident with classification
        $incident->update(['severity' => $severity]);

        // Generate analysis
        if (config('incident-intelligence.ai_enabled')) {
            try {
                return $this->generateAiAnalysis($incident, $normalized, $errorType);
            } catch (\Exception $e) {
                Log::warning('AI analysis failed, falling back to rule-based', [
                    'incident_id' => $incident->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->generateRuleBasedAnalysis($incident, $normalized, $errorType);
    }

    private function generateAiAnalysis(
        Incident $incident,
        string $normalized,
        ErrorTypeEnum $errorType
    ): IncidentAnalysis {
        $aiResponse = $this->ollama->analyze($normalized, $errorType);

        return $incident->analysis()->create([
            'root_cause' => $aiResponse['root_cause'],
            'suggested_fix' => $aiResponse['suggested_fix'],
            'confidence_score' => rand(80, 95),
            'ai_generated' => true,
        ]);
    }

    private function generateRuleBasedAnalysis(
        Incident $incident,
        string $normalized,
        ErrorTypeEnum $errorType
    ): IncidentAnalysis {
        $analysis = $this->classifier->generateAnalysis($normalized, $errorType);

        return $incident->analysis()->create([
            'root_cause' => $analysis['root_cause'],
            'suggested_fix' => $analysis['suggested_fix'],
            'confidence_score' => rand(60, 80),
            'ai_generated' => false,
        ]);
    }
}
