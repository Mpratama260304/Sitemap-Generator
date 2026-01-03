<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationProgress extends Model
{
    use HasFactory;

    protected $table = 'generation_progress';

    protected $fillable = [
        'project_id',
        'total_urls',
        'processed_urls',
        'current_file',
        'status',
        'last_error',
        'last_processed_id',
    ];

    protected $casts = [
        'total_urls' => 'integer',
        'processed_urls' => 'integer',
        'current_file' => 'integer',
        'last_processed_id' => 'integer',
    ];

    /**
     * Get the project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get progress percentage
     */
    public function getPercentageAttribute(): float
    {
        if ($this->total_urls === 0) {
            return 0;
        }

        return round(($this->processed_urls / $this->total_urls) * 100, 2);
    }

    /**
     * Check if generation is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if generation is in progress
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if generation failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get estimated files count
     */
    public function getEstimatedFilesAttribute(): int
    {
        $maxPerFile = config('sitemap.max_urls_per_file', 50000);
        return (int) ceil($this->total_urls / $maxPerFile);
    }
}
