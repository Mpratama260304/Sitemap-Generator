<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sitemap extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'index_path',
        'total_urls',
        'total_files',
        'generation_time',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'total_urls' => 'integer',
        'total_files' => 'integer',
        'generation_time' => 'float',
    ];

    /**
     * Get the project that owns this sitemap
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the sitemap files
     */
    public function files(): HasMany
    {
        return $this->hasMany(SitemapFile::class)->orderBy('file_number');
    }

    /**
     * Get the index URL
     */
    public function getIndexUrlAttribute(): string
    {
        return url($this->index_path);
    }

    /**
     * Get human-readable generation time
     */
    public function getFormattedGenerationTimeAttribute(): string
    {
        if ($this->generation_time === null) {
            return '-';
        }

        $seconds = $this->generation_time;
        
        if ($seconds < 60) {
            return number_format($seconds, 2) . ' detik';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $minutes . ' menit ' . number_format($remainingSeconds, 0) . ' detik';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . ' jam ' . $remainingMinutes . ' menit';
    }

    /**
     * Get total file size
     */
    public function getTotalFileSizeAttribute(): int
    {
        return $this->files->sum('file_size');
    }

    /**
     * Get formatted total file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->total_file_size;
        
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        if ($bytes < 1073741824) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        
        return number_format($bytes / 1073741824, 2) . ' GB';
    }
}
