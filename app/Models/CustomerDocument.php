<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CustomerDocument extends Model
{
    protected $fillable = [
        'customer_id', 'name', 'category', 'file_path', 'disk', 'file_type', 'file_size', 'uploaded_by',
    ];

    protected $casts = ['file_size' => 'integer'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getUrlAttribute(): string
    {
        return url('storage/' . ltrim($this->file_path, '/'));
    }

    public function getSizeHumanAttribute(): string
    {
        $size = (int) $this->file_size;
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($size < 1024) return round($size, 1) . ' ' . $unit;
            $size /= 1024;
        }

        return round($size, 1) . ' TB';
    }

    public function getIconClassAttribute(): string
    {
        return match (strtolower((string) $this->file_type)) {
            'pdf'               => 'text-danger',
            'doc', 'docx'       => 'text-primary',
            'xls', 'xlsx', 'csv' => 'text-success',
            'jpg', 'jpeg', 'png', 'webp' => 'text-info',
            default             => 'text-secondary',
        };
    }

    public function deleteFile(): void
    {
        Storage::disk($this->disk ?: 'public')->delete($this->file_path);
    }
}
