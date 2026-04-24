<?php

namespace App\Models;

use App\Enums\SeverityEnum;
use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Incident extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'message',
        'service',
        'severity',
        'hash',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'severity' => SeverityEnum::class,
            'status' => StatusEnum::class,
        ];
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(IncidentAnalysis::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(IncidentOccurrence::class);
    }

    public function getOccurrencesCountAttribute(): int
    {
        return $this->occurrences()->count() + 1; // +1 for original
    }
}
