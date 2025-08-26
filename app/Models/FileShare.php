<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class FileShare extends Model
{
    protected $fillable = [
        'identifier',
        'password_hash',
        'duration_seconds', 
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(UploadedFile::class);
    }

    public function getActiveFilesAttribute()
    {
        return $this->uploadedFiles()->where('expires_at', '>', now())->get();
    }

    public function hasPassword(): bool
    {
        return !empty($this->password_hash);
    }

    public function checkPassword(string $password): bool
    {
        return Hash::check($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Hash::make($password);
    }

    public function removePassword(): void
    {
        $this->password_hash = null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }
}
