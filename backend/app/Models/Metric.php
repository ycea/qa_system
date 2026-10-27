<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'options',
    ];

    protected $casts = [
        'options' => 'array', // автоматически jsonb ↔ array в PostgreSQL
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(MetricValue::class);
    }
}
