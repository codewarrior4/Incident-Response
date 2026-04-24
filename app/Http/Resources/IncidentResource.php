<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
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
            'title' => $this->title,
            'message' => $this->message,
            'service' => $this->service,
            'severity' => $this->severity,
            'status' => $this->status,
            'hash' => $this->hash,
            'occurrences_count' => $this->occurrences_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'analysis' => new IncidentAnalysisResource($this->whenLoaded('analysis')),
            'occurrences' => IncidentOccurrenceResource::collection($this->whenLoaded('occurrences')),
        ];
    }
}
