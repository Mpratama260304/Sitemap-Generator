<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitemapFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'sitemap_id',
        'file_path',
        'file_number',
        'url_count',
        'file_size',
    ];

    protected $casts = [
        'file_number' => 'integer',
        'url_count' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the sitemap that owns this file
     */
    public function sitemap(): BelongsTo
    {
        return $this->belongsTo(Sitemap::class);
    }

    /**
     * Get the file URL
     */
    public function getFileUrlAttribute(): string
    {
        return url($this->file_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    /**
     * Get the file name
     */
    public function getFileNameAttribute(): string
    {
        return basename($this->file_path);
    }
}
