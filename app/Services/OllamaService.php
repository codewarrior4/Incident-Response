<?php

namespace App\Services;

use App\Enums\ErrorTypeEnum;
use Illuminate\Support\Facades\Http;

class OllamaService
{
    public function analyze(string $normalizedMessage, ErrorTypeEnum $errorType): array
    {
        $url = config('incident-intelligence.ollama_url') . '/api/generate';
        $model = config('incident-intelligence.ollama_model');
        $timeout = config('incident-intelligence.ollama_timeout');

        $prompt = $this->buildPrompt($normalizedMessage, $errorType);

        $response = Http::timeout($timeout)->post($url, [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Ollama request failed: ' . $response->body());
        }

        return $this->parseResponse($response->json());
    }

    private function buildPrompt(string $message, ErrorTypeEnum $errorType): string
    {
        return <<<PROMPT
        You are an expert software engineer analyzing an error message.
        
        Error Type: {$errorType->value}
        Error Message: {$message}
        
        Provide a concise analysis in the following format:
        ROOT_CAUSE: [One sentence explaining the root cause]
        SUGGESTED_FIX: [One sentence with actionable fix]
        PROMPT;
    }

    private function parseResponse(array $response): array
    {
        $text = $response['response'] ?? '';

        preg_match('/ROOT_CAUSE:\s*(.+?)(?=SUGGESTED_FIX:|$)/s', $text, $rootCauseMatch);
        preg_match('/SUGGESTED_FIX:\s*(.+?)$/s', $text, $suggestedFixMatch);

        return [
            'root_cause' => trim($rootCauseMatch[1] ?? 'Unable to determine root cause'),
            'suggested_fix' => trim($suggestedFixMatch[1] ?? 'Manual investigation required'),
        ];
    }
}
