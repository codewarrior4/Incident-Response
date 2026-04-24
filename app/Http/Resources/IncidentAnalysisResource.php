<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'root_cause' => $this->root_cause,
            'suggested_fix' => $this->suggested_fix,
            'confidence_score' => $this->confidence_score,
            'ai_generated' => $this->ai_generated,
            'created_at' => $this->created_at,
        ];
    }
}
