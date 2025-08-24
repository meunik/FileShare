<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileShare extends Model
{
    protected $fillable = [
        'identifier',
    ];

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(UploadedFile::class);
    }

    public function getActiveFilesAttribute()
    {
        return $this->uploadedFiles()->where('expires_at', '>', now())->get();
    }
}
