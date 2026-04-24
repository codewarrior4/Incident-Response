<?php

namespace App\Services;

use App\Models\Incident;

class NormalizationService
{
    public function normalize(Incident $incident): string
    {
        $message = $incident->message;

        // Remove timestamps
        $message = preg_replace('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', '', $message);

        // Remove file paths
        $message = preg_replace('/\/[^\s]+\.php/', '[FILE_PATH]', $message);

        // Remove line numbers
        $message = preg_replace('/:\d+/', '', $message);

        // Remove specific IDs and UUIDs
        $message = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', '[UUID]', $message);
        $message = preg_replace('/\b\d{5,}\b/', '[ID]', $message);

        // Normalize whitespace
        $message = preg_replace('/\s+/', ' ', $message);

        return trim($message);
    }
}
