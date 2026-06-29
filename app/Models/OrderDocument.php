<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Polymorphic reference document attached to any order entity
 * (PurchaseOrder / SalesOrder / ProformaInvoice / CommercialInvoice).
 */
class OrderDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'documentable_type', 'documentable_id', 'name', 'category',
        'file_path', 'disk', 'file_type', 'extension', 'file_size',
        'icon_class', 'uploaded_by', 'version', 'notes', 'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version'   => 'integer',
        'is_active' => 'boolean',
    ];

    public function documentable() { return $this->morphTo(); }
    public function uploader()     { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getUrlAttribute(): string
    {
        return url('storage/' . ltrim($this->file_path, '/'));
    }

    public function getSizeHumanAttribute(): string
    {
        $size = (int) $this->file_size;
        if (!$size) return '—';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) { $size /= 1024; $i++; }
        return round($size, 1) . ' ' . $units[$i];
    }

    /** Map a file extension to the UI icon-colour class used by the order views. */
    public static function iconFor(string $ext): string
    {
        $ext = strtolower($ext);
        return match (true) {
            $ext === 'pdf'                              => 'pdf',
            in_array($ext, ['doc', 'docx'], true)       => 'word',
            in_array($ext, ['xls', 'xlsx', 'csv'], true) => 'excel',
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => 'img',
            default                                     => 'other',
        };
    }

    public function deleteFile(): void
    {
        Storage::disk($this->disk ?: 'public')->delete($this->file_path);
    }
}
