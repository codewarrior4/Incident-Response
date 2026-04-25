<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentAnalysis extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'incident_id',
        'root_cause',
        'suggested_fix',
        'confidence_score',
        'ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'integer',
            'ai_generated' => 'boolean',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
