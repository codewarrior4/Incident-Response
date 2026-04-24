<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentOccurrence extends Model
{
    use HasUuids;

    protected $fillable = [
        'incident_id',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
