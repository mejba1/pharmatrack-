<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'filename',
        'original_name',
        'path',
        'mime_type',
        'size',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'size'       => 'integer',
        'sort_order' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────

    /**
     * Full public URL for the image.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Human-readable file size.
     */
    public function getSizeHumanAttribute(): string
    {
        if (!$this->size) return '—';
        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = $this->size;
        $i     = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Delete the physical file from storage.
     */
    public function deleteFile(): void
    {
        Storage::disk('public')->delete($this->path);
    }
}
