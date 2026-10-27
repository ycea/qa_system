<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_id',
        'bug_id',
        'user_id',
        'numeric_value',
        'categorical_value',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'numeric_value' => 'decimal:4',
    ];

    public function metric(): BelongsTo
    {
        return $this->belongsTo(Metric::class);
    }

    public function bug(): BelongsTo
    {
        return $this->belongsTo(Bug::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
