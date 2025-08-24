<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UploadedFile extends Model
{
    protected $fillable = [
        'file_share_id',
        'original_name',
        'stored_name',
        'extension',
        'size',
        'mime_type',
        'duration_seconds',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function fileShare(): BelongsTo
    {
        return $this->belongsTo(FileShare::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    public function delete()
    {
        // Remove o arquivo do storage antes de deletar o registro
        if (Storage::disk('local')->exists('uploads/' . $this->stored_name)) {
            Storage::disk('local')->delete('uploads/' . $this->stored_name);
        }
        
        return parent::delete();
    }
}
